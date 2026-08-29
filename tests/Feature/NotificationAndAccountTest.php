<?php

namespace Tests\Feature;

use App\Mail\NotificationAlertMail;
use App\Models\Account;
use App\Models\Family;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationAndAccountTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Family $family;

    protected function setUp(): void
    {
        parent::setUp();

        $this->family = Family::create([
            'name' => '測試家庭',
            'code' => 'TEST1234',
        ]);

        $this->user = User::create([
            'name' => '測試成員',
            'account' => 'testuser',
            'email' => 'testuser@example.com',
            'password' => bcrypt('password'),
            'current_family_id' => $this->family->id,
        ]);
    }

    public function test_notification_creation_triggers_email_mailable(): void
    {
        Mail::fake();

        $notification = Notification::create([
            'user_id' => $this->user->id,
            'family_id' => $this->family->id,
            'type' => 'task_approval',
            'title' => '孩童回報家事任務',
            'body' => '小明 已完成「收拾房間」任務，請撥空審核與發放獎勵金。',
        ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'user_id' => $this->user->id,
            'title' => '孩童回報家事任務',
        ]);

        Mail::assertSent(NotificationAlertMail::class, function ($mail) use ($notification) {
            return $mail->hasTo('testuser@example.com') &&
                   $mail->notification->id === $notification->id;
        });

        $this->assertNotNull($notification->fresh()->sent_at);
    }

    public function test_notification_mark_as_read_and_mark_all_read(): void
    {
        $n1 = Notification::create([
            'user_id' => $this->user->id,
            'family_id' => $this->family->id,
            'type' => 'budget_alert',
            'title' => '預算警告',
            'body' => '飲食類別預算已達 90%',
        ]);

        $n2 = Notification::create([
            'user_id' => $this->user->id,
            'family_id' => $this->family->id,
            'type' => 'invitation',
            'title' => '家庭邀請',
            'body' => '您被邀請加入家庭',
        ]);

        $this->actingAs($this->user);

        // Single mark as read
        $response = $this->postJson("/notifications/{$n1->id}/read");
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertNotNull($n1->fresh()->read_at);

        // Mark all as read
        $responseAll = $this->postJson('/notifications/mark-all-read');
        $responseAll->assertStatus(200)->assertJson(['success' => true]);
        $this->assertNotNull($n2->fresh()->read_at);
    }

    public function test_notification_deletion(): void
    {
        $notification = Notification::create([
            'user_id' => $this->user->id,
            'family_id' => $this->family->id,
            'type' => 'system',
            'title' => '測試通知刪除',
            'body' => '即將刪除此通知',
        ]);

        $this->actingAs($this->user);

        $response = $this->deleteJson("/notifications/{$notification->id}");
        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_account_creation_editing_and_deletion(): void
    {
        $this->actingAs($this->user);

        // 1. Create account
        $createResponse = $this->post('/accounts', [
            'name' => '玉山銀行主帳戶',
            'type' => 'bank',
            'balance' => 50000.00,
            'currency' => 'TWD',
            'color' => '#006b5f',
            'icon' => 'account_balance',
        ]);

        $createResponse->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'name' => '玉山銀行主帳戶',
            'balance' => 50000.00,
        ]);

        $account = Account::where('name', '玉山銀行主帳戶')->firstOrFail();

        // 2. Edit account
        $editResponse = $this->put("/accounts/{$account->id}", [
            'name' => '玉山銀行尊榮帳戶',
            'type' => 'bank',
            'balance' => 65000.00,
            'currency' => 'TWD',
            'color' => '#3B82F6',
            'icon' => 'account_balance',
        ]);

        $editResponse->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => '玉山銀行尊榮帳戶',
            'balance' => 65000.00,
        ]);

        // 3. Delete account
        $deleteResponse = $this->delete("/accounts/{$account->id}");
        $deleteResponse->assertRedirect(route('accounts.index'));
        $this->assertDatabaseMissing('accounts', [
            'id' => $account->id,
        ]);
    }

    public function test_internal_transfer_form_submission_and_balances(): void
    {
        $this->actingAs($this->user);

        $fromAcc = Account::create([
            'family_id' => $this->family->id,
            'name' => '國泰薪轉戶',
            'type' => 'bank',
            'balance' => 30000.00,
            'currency' => 'TWD',
        ]);

        $toAcc = Account::create([
            'family_id' => $this->family->id,
            'name' => '日常現金',
            'type' => 'cash',
            'balance' => 2000.00,
            'currency' => 'TWD',
        ]);

        $transferResponse = $this->post('/accounts/transfer', [
            'from_account_id' => $fromAcc->id,
            'to_account_id' => $toAcc->id,
            'amount' => 5000.00,
            'occurred_at' => date('Y-m-d'),
            'description' => 'ATM 提領現金 5000 元',
        ]);

        $transferResponse->assertRedirect(route('accounts.index'));

        $this->assertEquals(25000.00, $fromAcc->fresh()->balance);
        $this->assertEquals(7000.00, $toAcc->fresh()->balance);

        $this->assertDatabaseHas('transactions', [
            'type' => 'transfer',
            'amount' => 5000.00,
            'account_id' => $fromAcc->id,
            'to_account_id' => $toAcc->id,
        ]);
    }
}
