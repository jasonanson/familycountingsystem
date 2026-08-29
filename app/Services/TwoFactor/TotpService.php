<?php

namespace App\Services\TwoFactor;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * TOTP (RFC 6238) 服務 - 純 PHP 實作，不需外部套件。
 *
 * 規格：
 *   - 演算法：HMAC-SHA1（與 Google Authenticator / Authy 相容）
 *   - 週期：30 秒
 *   - 數字位數：6
 *   - Secret：Base32 編碼（26 字元）
 *
 * 待日後 composer 網路解封，可無痛切換為 pragmarx/google2fa + pragmarx/google2fa-laravel：
 *   - 此類別的公開 API 與上述套件的方法簽名一致
 *   - 替換只須改 use 與實作，不需動 controller / view
 */
class TotpService
{
    private const PERIOD = 30;
    private const DIGITS = 6;
    private const ALGO   = 'sha1';
    private const WINDOW = 1;

    public function generateSecret(): string
    {
        $random = random_bytes(16);
        return $this->base32Encode($random);
    }

    public function base32Encode(string $bytes): string
    {
        if ($bytes === '') return '';
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        foreach (str_split($bytes) as $byte) {
            $binary .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }
        $output = '';
        foreach (str_split($binary, 5) as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $output .= $alphabet[bindec($chunk)];
        }
        return $output;
    }

    public function base32Decode(string $base32): string
    {
        $base32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $base32) ?? '');
        if ($base32 === '') return '';

        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        foreach (str_split($base32) as $char) {
            $binary .= str_pad(decbin(strpos($alphabet, $char)), 5, '0', STR_PAD_LEFT);
        }
        $output = '';
        foreach (str_split($binary, 8) as $byte) {
            if (strlen($byte) === 8) {
                $output .= chr(bindec($byte));
            }
        }
        return $output;
    }

    public function currentCode(string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $counter = intdiv($timestamp, self::PERIOD);
        return $this->generateCode($secret, $counter);
    }

    public function verifyCode(string $secret, string $code, ?int $timestamp = null, bool $reuseLock = true): bool
    {
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if (!preg_match('/^\d{' . self::DIGITS . '}$/', $code)) {
            return false;
        }
        $timestamp = $timestamp ?? time();
        $counter = intdiv($timestamp, self::PERIOD);

        for ($i = -self::WINDOW; $i <= self::WINDOW; $i++) {
            $candidate = $this->generateCode($secret, $counter + $i);
            if (hash_equals($candidate, $code)) {
                if ($reuseLock) {
                    $lockKey = $this->reuseLockKey($secret, $counter + $i);
                    if (Cache::has($lockKey)) {
                        return false;
                    }
                    Cache::put($lockKey, true, self::PERIOD * (2 * self::WINDOW + 1));
                }
                return true;
            }
        }
        return false;
    }

    public function qrCodeUrl(string $secret, string $accountName, string $issuer = 'HomeSync Finance'): string
    {
        $label = rawurlencode($issuer . ':' . $accountName);
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => strtoupper(self::ALGO),
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);
        return "otpauth://totp/{$label}?{$params}";
    }

    /**
     * @return array<int, string>
     */
    public function generateRecoveryCodes(int $count = 10): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $a = strtoupper(Str::random(5));
            $b = strtoupper(Str::random(5));
            $codes[] = "{$a}-{$b}";
        }
        return $codes;
    }

    /**
     * @param  array<int, string>  $codes
     * @return array<int, string>
     */
    public function hashRecoveryCodes(array $codes): array
    {
        return array_map(fn ($c) => Hash::make($c), $codes);
    }

    /**
     * @param  array<int, string>  $hashedList
     * @return array{ok: bool, remaining: array<int, string>}
     */
    public function consumeRecoveryCode(array $hashedList, string $plain): array
    {
        $plain = strtoupper(trim($plain));
        foreach ($hashedList as $idx => $hash) {
            if (Hash::check($plain, $hash)) {
                $remaining = $hashedList;
                unset($remaining[$idx]);
                return ['ok' => true, 'remaining' => array_values($remaining)];
            }
        }
        return ['ok' => false, 'remaining' => $hashedList];
    }

    /**
     * @return array{secret: string, recovery_codes: array<int, string>}
     */
    public function enableFor(User $user): array
    {
        $secret = $this->generateSecret();
        $recoveryCodes = $this->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $this->hashRecoveryCodes($recoveryCodes),
        ])->save();

        return ['secret' => $secret, 'recovery_codes' => $recoveryCodes];
    }

    public function disableFor(User $user): void
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();
    }

    /**
     * @return array<int, string>
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        $codes = $this->generateRecoveryCodes();
        $user->forceFill([
            'two_factor_recovery_codes' => $this->hashRecoveryCodes($codes),
        ])->save();
        return $codes;
    }

    private function generateCode(string $secret, int $counter): string
    {
        $key = $this->base32Decode($secret);
        $binCounter = '';
        for ($i = 7; $i >= 0; $i--) {
            $binCounter .= chr(($counter >> ($i * 8)) & 0xff);
        }
        $hash = hash_hmac(self::ALGO, $binCounter, $key, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0xf;
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % (10 ** self::DIGITS);
        return str_pad((string) $code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    private function reuseLockKey(string $secret, int $counter): string
    {
        return '2fa:used:' . substr(hash('sha256', $secret . ':' . $counter), 0, 16);
    }
}
