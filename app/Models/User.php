<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'account',
        'phone',
        'avatar_url',
        'two_factor_secret',
        'failed_login_count',
        'locked_until',
        'is_system_admin',
        'current_family_id',
        'password',
        'registration_role',
        'notification_preferences',
        'locale',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'locked_until' => 'datetime',
            'is_system_admin' => 'boolean',
            'notification_preferences' => 'array',
            'two_factor_enabled' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_recovery_codes' => 'array',
            'password' => 'hashed',
        ];
    }

    public function families(): BelongsToMany
    {
        return $this->belongsToMany(Family::class, 'family_user')
            ->withPivot(['role', 'display_name', 'joined_at', 'is_active'])
            ->withTimestamps();
    }

    public function activeFamilies(): BelongsToMany
    {
        return $this->families()->wherePivot('is_active', true);
    }

    /**
     * 我所「擁有」的家庭清單（owner_user_id = id）。
     * 使用 Family::withoutGlobalScopes() 取得，避免被 FamilyScope 遮蔽。
     */
    public function ownedFamilies()
    {
        return Family::withoutGlobalScopes()->where('owner_user_id', $this->id);
    }

    /**
     * 是否為系統管理員（is_system_admin = true）。
     */
    public function isAdmin(): bool
    {
        return (bool) $this->is_system_admin;
    }

    /**
     * 是否為家長身份（依 registration_role 或任一家庭的樞紐角色為 parent）。
     */
    public function isParent(): bool
    {
        if ($this->registration_role === 'parent') {
            return true;
        }

        return DB::table('family_user')
            ->where('user_id', $this->id)
            ->where('role', 'parent')
            ->where('is_active', true)
            ->exists();
    }

    /**
     * 是否為小孩身份。
     */
    public function isChild(): bool
    {
        if ($this->registration_role === 'child') {
            return true;
        }

        return DB::table('family_user')
            ->where('user_id', $this->id)
            ->where('role', 'child')
            ->where('is_active', true)
            ->exists();
    }

    /**
     * 是否允許「手動建立新家庭」。
     *
     * 規則：
     *   - admin（系統管理員）：永遠允許，可以建立多個家庭。
     *   - parent（家長）：只有在「尚未擁有家庭」時才允許，
     *     一旦已經擁有 1 個家庭，之後就不可再手動建立。
     *   - child（小孩）：永遠不允許（小孩只能透過邀請加入家庭）。
     *   - 其他（member / guest）：不允許手動建立家庭。
     */
    public function canCreateFamily(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        // 小孩絕對不能建立家庭
        if ($this->isChild()) {
            return false;
        }

        // 家長僅能在「尚未擁有家庭」時建立第一個
        if ($this->isParent()) {
            return $this->ownedFamilies()->count() === 0;
        }

        // member / guest 等角色不允許
        return false;
    }

    public function currentFamily(): BelongsTo
    {
        return $this->belongsTo(Family::class, 'current_family_id')->withoutGlobalScopes();
    }

    public function currentFamilyRole(): ?string
    {
        if (! $this->current_family_id) {
            return null;
        }

        if ($this->relationLoaded('families')) {
            $family = $this->families->firstWhere('id', $this->current_family_id);
            if ($family) {
                return $family->pivot->role ?? null;
            }
        }

        $pivot = \Illuminate\Support\Facades\DB::table('family_user')
            ->where('user_id', $this->id)
            ->where('family_id', $this->current_family_id)
            ->first();

        return $pivot?->role;
    }

    public function isParentInCurrentFamily(): bool
    {
        return $this->currentFamilyRole() === 'parent';
    }

    /**
     * 判斷當前登入者是否可編輯 current_family 的資料。
     *
     * 規則：
     *   - 沒有 current_family_id → false
     *   - 一般使用者：必須是當前家庭的 parent (透過 family_user.role 判斷)
     *   - admin：必須在當前家庭的 family_user 中以 parent 角色存在
     *     （即「已被家庭家長邀請」後才可編輯；純 admin 只能看不能改）
     */
    public function canEditCurrentFamily(): bool
    {
        if (! $this->current_family_id) {
            return false;
        }

        return DB::table('family_user')
            ->where('user_id', $this->id)
            ->where('family_id', $this->current_family_id)
            ->where('role', 'parent')
            ->where('is_active', true)
            ->exists();
    }

    /**
     * 判斷是否可編輯指定家庭（不限定為 current_family）。
     */
    public function canEditFamily(int $familyId): bool
    {
        return DB::table('family_user')
            ->where('user_id', $this->id)
            ->where('family_id', $familyId)
            ->where('role', 'parent')
            ->where('is_active', true)
            ->exists();
    }

    /**
     * 是否為家庭的管理員檢視者 (admin 但未被邀請加入該家庭)。
     * 用於 UI 顯示「唯讀模式」提示。
     */
    public function isReadOnlyViewerOfCurrentFamily(): bool
    {
        if (! $this->is_system_admin) {
            return false;
        }
        if (! $this->current_family_id) {
            return false; // 沒切換家庭就不算檢視者
        }
        return ! $this->canEditCurrentFamily();
    }

        /**
     * 確保當前使用者有一個可用的家庭。
     *
     * 解決「family_id null」Bug：
     * 當 User.current_family_id 為 NULL（例：舊資料、註冊流程中斷、
     * 從未進入家庭建立流程就被自動登入等），FamilyScope 會把所有
     * Transaction / Category / Account 的查詢都遮蔽掉，導致快速記帳、
     * 預算、儀表板統計全部壞掉。
     *
     * 此方法會：
     *  1. 若已有 current_family_id，直接回傳 currentFamily；
     *  2. 若 family_user 仍有有效紀錄，自動撿第一筆設為 current_family_id；
     *  3. 若完全沒有家庭，依角色決定是否自動建立預設家庭：
     *     - admin：不自動建立，current_family_id 保持 null，
     *       由管理員於「後台 → 家庭管理」手動建立 / 加入家庭。
     *     - child：不自動建立（家長必須先建立家庭再邀請）。
     *     - parent：只有在「尚未擁有家庭」時才建立第一個，否則保持 null。
     *
     * 注意：寫入 family_user 與讀取樞紐時刻意走 DB facade，不走
     * Family::families() 關聯，避免被 FamilyScope 的全域範圍遮蔽。
     */
    public function ensureHasFamily(): ?Family
    {
        // 已綁定就不要再寫一次，避免把使用者目前正在切換的家庭覆蓋掉。
        if ($this->current_family_id) {
            return $this->currentFamily;
        }

        // 用 DB facade 直查樞紐表，繞過 FamilyScope。
        $existingPivot = DB::table('family_user')
            ->where('user_id', $this->id)
            ->where('is_active', true)
            ->orderBy('joined_at')
            ->orderBy('id')
            ->first();

        if (! $existingPivot) {
            // 小孩身份：不可自動創建家庭，必須等待家長 / 管理員邀請。
            if ($this->registration_role === 'child') {
                return null;
            }

            // 家長或管理員身份：只有在「尚未擁有家庭」時才建立第一個。
            if (($this->isParent() || $this->isAdmin()) && $this->ownedFamilies()->count() > 0) {
                return null;
            }

            // 非大人（member / guest 等）：不自動建立。
            if (! $this->isParent() && ! $this->isAdmin()) {
                return null;
            }

            // 完全沒有家庭的大人（包含系統管理員與家長）：建立預設家庭，把使用者掛為家長。
            $family = Family::withoutEvents(function () {
                return Family::create([
                    'name' => $this->name . '的家庭',
                    'currency' => 'TWD',
                    'invite_code' => strtoupper(Str::random(6)),
                    'total_pool_amount' => 0,
                    'created_by_user_id' => $this->id,
                    'owner_user_id' => $this->id,
                ]);
            });

            DB::table('family_user')->insert([
                'user_id' => $this->id,
                'family_id' => $family->id,
                'role' => 'parent',
                'is_active' => true,
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 自動建立一筆預設「日常現金」帳戶
            Account::withoutEvents(function () use ($family) {
                return Account::create([
                    'family_id' => $family->id,
                    'name' => '日常現金',
                    'type' => 'cash',
                    'balance' => 0.00,
                    'currency' => 'TWD',
                    'color' => '#10B981',
                    'icon' => 'heroicon-o-banknotes',
                ]);
            });

            $this->forceFill(['current_family_id' => $family->id])->save();
        } else {
            $this->forceFill(['current_family_id' => $existingPivot->family_id])->save();
        }
        $this->refresh();

        // Auth 守衛可能快取了 $this 的舊實例（含 current_family_id = null），
        // 強制替換成新的實例，否則 FamilyScope 套用時仍會把 $this->currentFamily 視為 null。
        if (\Illuminate\Support\Facades\Auth::check()
            && \Illuminate\Support\Facades\Auth::id() === $this->id) {
            \Illuminate\Support\Facades\Auth::setUser($this);
        }

        return $this->currentFamily;
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function savingGoals(): HasMany
    {
        return $this->hasMany(SavingGoal::class);
    }

    public function childLimit(): HasMany
    {
        return $this->hasMany(ChildLimit::class, 'child_user_id');
    }
}
