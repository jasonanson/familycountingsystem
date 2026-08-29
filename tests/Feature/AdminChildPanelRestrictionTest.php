<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminChildPanelRestrictionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $child;
    protected Family $family;

    protected function setUp(): void
    {
        parent::setUp();

        $this->family = Family::create([
            'name' => '測試幸福家庭',
            'invite_code' => 'ADMIN123',
        ]);

        $this->admin = User::create([
            'name' => '系統管理員大人',
            'account' => 'admin_user',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_system_admin' => true,
            'current_family_id' => $this->family->id,
            'registration_role' => 'parent',
        ]);

        $this->child = User::create([
            'name' => '小明孩童',
            'account' => 'child_user',
            'email' => 'child@example.com',
            'password' => bcrypt('password'),
            'is_system_admin' => false,
            'current_family_id' => $this->family->id,
            'registration_role' => 'child',
        ]);

        $this->family->members()->attach($this->admin->id, ['role' => 'parent']);
        $this->family->members()->attach($this->child->id, ['role' => 'child']);
    }

    public function test_admin_can_view_child_dashboard_with_restriction_banner(): void
    {
        $response = $this->actingAs($this->admin)->get('/child-dashboard');

        $response->assertStatus(200);
        $response->assertSee('系統管理員檢視模式（唯讀限制）');
        $response->assertSee('大人身分限制');
        $response->assertSee('🔒 管理員限制使用');
    }

    public function test_admin_can_view_child_wallet_with_restriction_banner_and_disabled_actions(): void
    {
        $response = $this->actingAs($this->admin)->get('/child-wallet');

        $response->assertStatus(200);
        $response->assertSee('系統管理員檢視模式（唯讀限制）');
        $response->assertSee('大人身分限制');
        $response->assertSee('🔒 管理員限制');
    }

    public function test_admin_cannot_report_task_completion_as_child(): void
    {
        $task = Task::create([
            'family_id' => $this->family->id,
            'name' => '擦客廳桌子',
            'reward_amount' => 50,
            'assignee_user_id' => $this->child->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->post("/tasks/{$task->id}/report");

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'pending',
        ]);
    }

    public function test_child_can_report_task_completion_normally(): void
    {
        $task = Task::create([
            'family_id' => $this->family->id,
            'name' => '自己洗襪子',
            'reward_amount' => 30,
            'assignee_user_id' => $this->child->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->child)->post("/tasks/{$task->id}/report");

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'reported',
        ]);
    }

    public function test_admin_can_view_saving_goals_with_read_only_banner(): void
    {
        $response = $this->actingAs($this->admin)->get('/saving-goals');

        $response->assertStatus(200);
        $response->assertSee('系統管理員檢視模式（唯讀限制）');
        $response->assertSee('管理員唯讀檢視');
        $response->assertDontSee('+ 新增儲蓄目標');
    }

    public function test_admin_cannot_create_or_delete_saving_goals(): void
    {
        // 嘗試新增目標
        $createResponse = $this->actingAs($this->admin)->post('/saving-goals', [
            'name' => '買Switch遊戲',
            'target_amount' => 1500,
        ]);

        $createResponse->assertSessionHas('error');
        $this->assertDatabaseMissing('saving_goals', [
            'name' => '買Switch遊戲',
        ]);

        // 兒童建立一個目標
        $goal = \App\Models\SavingGoal::create([
            'family_id' => $this->family->id,
            'user_id' => $this->child->id,
            'name' => '兒童樂高積木',
            'target_amount' => 2000,
            'current_amount' => 500,
        ]);

        // 管理員嘗試刪除
        $deleteResponse = $this->actingAs($this->admin)->delete("/saving-goals/{$goal->id}");
        $deleteResponse->assertSessionHas('error');
        $this->assertDatabaseHas('saving_goals', [
            'id' => $goal->id,
        ]);

        // 管理員嘗試存入
        $depositResponse = $this->actingAs($this->admin)->post("/saving-goals/{$goal->id}/deposit", [
            'amount' => 200,
        ]);
        $depositResponse->assertSessionHas('error');
    }

    public function test_child_can_create_saving_goals_normally(): void
    {
        $response = $this->actingAs($this->child)->post('/saving-goals', [
            'name' => '新書包',
            'target_amount' => 800,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('saving_goals', [
            'name' => '新書包',
            'user_id' => $this->child->id,
        ]);
    }
}