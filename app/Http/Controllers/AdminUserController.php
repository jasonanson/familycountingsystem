<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Family;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    protected function checkAdmin()
    {
        if (! auth()->check() || ! auth()->user()->is_system_admin) {
            abort(403, '存取拒絕：權限不足，只有最高系統管理員可以訪問管理介面。');
        }
    }

    public function index(Request $request)
    {
        $this->checkAdmin();
        
        $familyFilter = $request->get('family_id');
        $roleFilter = $request->get('role');
        $search = $request->get('search');

        $query = User::with(['families', 'currentFamily']);

        // [P3] 多欄位排序：sort_by + sort_dir 查詢參數
        // 允許的欄位（白名單避免 SQL injection）
        $allowedSort = ['id', 'name', 'email', 'account', 'created_at', 'is_system_admin'];
        $sortBy = $request->get('sort_by', 'id');
        $sortDir = strtolower($request->get('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'id';
        }

        // 預設排序：管理員優先，然後用使用者選擇的欄位
        $query->orderBy('is_system_admin', 'desc')->orderBy($sortBy, $sortDir);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('account', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($familyFilter === 'none') {
            $query->whereDoesntHave('families');
        } elseif (!empty($familyFilter) && $familyFilter !== 'all') {
            $query->whereHas('families', function ($q) use ($familyFilter) {
                $q->where('families.id', $familyFilter);
            });
        }

        if ($roleFilter === 'admin') {
            $query->where('is_system_admin', true);
        } elseif ($roleFilter === 'child') {
            $query->where(function ($q) {
                $q->where('registration_role', 'child')
                  ->orWhereHas('families', function ($fq) {
                      $fq->where('family_user.role', 'child');
                  });
            });
        } elseif ($roleFilter === 'parent') {
            $query->where(function ($q) {
                $q->where('registration_role', 'parent')
                  ->orWhereHas('families', function ($fq) {
                      $fq->where('family_user.role', 'parent');
                  });
            });
        }

        $users = $query->paginate(15)->withQueryString();
        $allFamilies = Family::orderBy('name')->get();

        return view('admin.users.index', compact('users', 'allFamilies', 'familyFilter', 'roleFilter', 'search', 'sortBy', 'sortDir'));
    }

    public function create()
    {
        $this->checkAdmin();
        $families = Family::all();
        return view('admin.users.create', compact('families'));
    }

    public function store(Request $request)
    {
        $this->checkAdmin();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'account' => 'required|string|unique:users,account|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'password' => 'required|string|min:6',
            'is_system_admin' => 'nullable|boolean',
            'family_id' => 'nullable|exists:families,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'account' => $validated['account'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_system_admin' => $request->boolean('is_system_admin'),
            'role' => $request->input('family_role', 'parent'),
            'registration_role' => $request->input('family_role', 'parent'),
        ]);

        if (! empty($validated['family_id'])) {
            $roleInFamily = $request->input('family_role', 'parent');
            $user->families()->attach($validated['family_id'], ['role' => $roleInFamily, 'is_active' => true]);
            $user->update(['current_family_id' => $validated['family_id']]);
        }

        return redirect()->route('admin.users.index')->with('success', '使用者帳號建立成功！');
    }

    public function update(Request $request, User $user)
    {
        $this->checkAdmin();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'account' => 'nullable|string|unique:users,account,' . $user->id,
            'password' => 'nullable|string|min:6',
            'registration_role' => 'nullable|string|in:parent,child,member,guest',
            'family_id' => 'nullable',
            'family_role' => 'nullable|string|in:parent,child,viewer',
            'detach_family_id' => 'nullable|exists:families,id',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        if ($request->filled('registration_role')) {
            $user->registration_role = $request->input('registration_role');
        }
        if (! empty($validated['account'])) {
            $user->account = $validated['account'];
        }
        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        if ($request->has('detach_family_id') && filled($request->detach_family_id)) {
            $user->families()->detach($request->detach_family_id);
            if ($user->current_family_id == $request->detach_family_id) {
                $user->update(['current_family_id' => $user->families()->first()?->id]);
            }
        }

        if ($request->has('family_id')) {
            $familyId = $request->input('family_id');
            $roleInFamily = $request->input('family_role', 'parent');
            if ($familyId && $familyId !== 'none') {
                $user->families()->syncWithoutDetaching([$familyId => ['role' => $roleInFamily, 'is_active' => true]]);
                $user->update(['current_family_id' => $familyId]);
            } elseif ($familyId === 'none' || $familyId === '') {
                $user->update(['current_family_id' => null]);
            }
        }

        return redirect()->route('admin.users.index')->with('success', '使用者資料已成功更新！');
    }

    public function destroy(User $user)
    {
        $this->checkAdmin();
        // 防護：禁止最高管理員刪除自己
        if (auth()->id() === $user->id) {
            return redirect()->route('admin.users.index')->with('error', '⚠️ 安全防護：無法刪除您自己目前的最高管理員帳號！');
        }

        $user->delete();
        return redirect()->route('admin.users.index')->with('success', '使用者帳號已成功刪除！');
    }

    public function toggleSystemAdmin(User $user)
    {
        $this->checkAdmin();
        // 防護：禁止最高管理員取消自己的管理權限
        if (auth()->id() === $user->id) {
            return redirect()->route('admin.users.index')->with('error', '⚠️ 安全防護：無法撤銷您自己目前的最高管理員權限！');
        }

        $user->is_system_admin = ! $user->is_system_admin;
        $user->save();

        return redirect()->route('admin.users.index')->with('success', '使用者管理員權限狀態已更新！');
    }

    /**
     * 管理員為使用者指派/加入家庭（支援指派多個家庭與指定角色）
     */
    public function attachFamily(Request $request, User $user)
    {
        $this->checkAdmin();

        $validated = $request->validate([
            'family_id' => 'required|exists:families,id',
            'role' => 'required|string|in:parent,child,viewer,guest,member',
            'set_as_current' => 'nullable|boolean',
        ]);

        $family = Family::findOrFail($validated['family_id']);
        $rawRole = $validated['role'];
        $dbRole = ($rawRole === 'viewer' || $rawRole === 'guest') ? 'guest' : ($rawRole === 'child' ? 'child' : 'parent');

        $user->families()->syncWithoutDetaching([
            $family->id => [
                'role' => $dbRole,
                'is_active' => true,
            ]
        ]);

        if ($request->boolean('set_as_current') || ! $user->current_family_id) {
            $user->update(['current_family_id' => $family->id]);
        }

        $roleLabel = match($dbRole) {
            'child' => '小孩 / 兒童',
            'parent' => '大人 / 家長',
            'guest' => '觀察者 / 訪客',
            default => '成員'
        };

        return redirect()->route('admin.users.index')->with('success', "已成功為使用者【{$user->name}】指派家庭【{$family->name}】（角色：{$roleLabel}）！");
    }

    /**
     * 變更使用者在特定家庭中的權限角色
     */
    public function updateFamilyRole(Request $request, User $user, Family $family)
    {
        $this->checkAdmin();

        $validated = $request->validate([
            'role' => 'required|string|in:parent,child,viewer,guest,member',
        ]);

        $rawRole = $validated['role'];
        $dbRole = ($rawRole === 'viewer' || $rawRole === 'guest') ? 'guest' : ($rawRole === 'child' ? 'child' : 'parent');

        $user->families()->updateExistingPivot($family->id, [
            'role' => $dbRole,
        ]);

        $roleLabel = match($dbRole) {
            'child' => '小孩 / 兒童',
            'parent' => '大人 / 家長',
            'guest' => '觀察者 / 訪客',
            default => '成員'
        };

        return redirect()->route('admin.users.index')->with('success', "使用者【{$user->name}】在【{$family->name}】的角色權限已變更為【{$roleLabel}】！");
    }

    /**
     * 將使用者從特定家庭移出（踢出家庭，撤銷檢視與記帳權限）
     */
    public function detachFamily(User $user, Family $family)
    {
        $this->checkAdmin();

        $user->families()->detach($family->id);

        // 若移出的剛好是目前主要家庭，自動平滑切換到下一個可用家庭
        if ($user->current_family_id == $family->id) {
            $nextFamily = $user->families()->first();
            $user->update(['current_family_id' => $nextFamily?->id]);
        }

        return redirect()->route('admin.users.index')->with('success', "已成功將使用者【{$user->name}】從【{$family->name}】移出（已撤銷檢視權限）！");
    }

    /**
     * 一鍵將使用者從所有家庭移出（清空家庭權限）
     */
    public function detachAllFamilies(User $user)
    {
        $this->checkAdmin();

        $user->families()->detach();
        $user->update(['current_family_id' => null]);

        return redirect()->route('admin.users.index')->with('success', "已成功撤銷使用者【{$user->name}】的所有家庭檢視權限（已移出所有家庭）！");
    }

    /**
     * 將特定家庭設為該使用者的主要檢視家庭 (current_family_id)
     */
    public function setPrimaryFamily(User $user, Family $family)
    {
        $this->checkAdmin();

        if (! $user->families()->where('families.id', $family->id)->exists()) {
            return redirect()->route('admin.users.index')->with('error', "該使用者尚未加入【{$family->name}】，無法設為主要家庭。");
        }

        $user->update(['current_family_id' => $family->id]);

        return redirect()->route('admin.users.index')->with('success', "已成功將【{$family->name}】設為使用者【{$user->name}】的主要預設家庭！");
    }
}
