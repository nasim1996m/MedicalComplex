<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Accounts
{
    /**
     * Finds the user for a verified Google identity, or registers a new pending account
     * (no role, no access) and notifies the admin.
     */
    public static function fromGoogle(array $identity): User
    {
        $user = User::where('email', $identity['email'])->first();

        if ($user) {
            // Never let a different Google account take over an email already linked to another one.
            if ($user->google_id && $user->google_id !== $identity['google_id']) {
                abort(403, 'هذا البريد مرتبط بحساب Google آخر');
            }
            $user->google_id = $user->google_id ?: $identity['google_id'];
            $user->avatar = $user->avatar ?: $identity['avatar'];
            $user->save();

            return $user;
        }

        return DB::transaction(function () use ($identity) {
            $user = new User([
                'name' => Str::limit($identity['name'], 100, ''),
                'email' => $identity['email'],
                'google_id' => $identity['google_id'],
                'avatar' => $identity['avatar'],
            ]);
            $user->role = 'pending';
            $user->status = 'pending';
            $user->save();

            DB::table('notifications')->insert([
                'user_id' => null,
                'target_role' => 'admin',
                'title' => 'طلب انضمام جديد',
                'message' => 'سجل ' . $user->name . ' (' . $user->email . ') بحساب Google ويطلب الانضمام',
                'type' => 'role_request',
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $user;
        });
    }

    /** Creates or updates the user's single pending role request. */
    public static function submitRoleRequest(User $user, string $role, ?string $specialty, ?string $notes): int
    {
        return DB::transaction(function () use ($user, $role, $specialty, $notes) {
            $data = ['requested_role' => $role, 'requested_specialty' => $specialty, 'notes' => $notes, 'updated_at' => now()];
            $existing = DB::table('role_requests')->where('user_id', $user->id)->where('status', 'pending')->first();

            if ($existing) {
                DB::table('role_requests')->where('id', $existing->id)->update($data);
                $id = $existing->id;
            } else {
                $id = DB::table('role_requests')->insertGetId($data + ['user_id' => $user->id, 'status' => 'pending', 'created_at' => now()]);
            }

            DB::table('notifications')->insert([
                'user_id' => null,
                'target_role' => 'admin',
                'title' => 'طلب انضمام جديد',
                'message' => 'قدم ' . $user->name . ' (' . $user->email . ') طلب فتح داشبورد ' . $role,
                'type' => 'role_request',
                'is_read' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $id;
        });
    }

    /** Approves or rejects a pending role request; returns false if it was not pending. */
    public static function review(int $requestId, User $admin, bool $approve): bool
    {
        return DB::transaction(function () use ($requestId, $admin, $approve) {
            $req = DB::table('role_requests')->where('id', $requestId)->lockForUpdate()->first();
            if (!$req || $req->status !== 'pending' || !in_array($req->requested_role, User::REQUESTABLE_ROLES, true)) {
                return false;
            }

            DB::table('role_requests')->where('id', $req->id)->update([
                'status' => $approve ? 'approved' : 'rejected',
                'reviewed_by' => $admin->id,
                'updated_at' => now(),
            ]);

            DB::table('users')->where('id', $req->user_id)->update($approve
                ? ['role' => $req->requested_role, 'specialty' => $req->requested_specialty, 'status' => 'approved', 'updated_at' => now()]
                : ['status' => 'rejected', 'updated_at' => now()]);

            if ($approve) {
                DB::table('notifications')->insert([
                    'user_id' => $req->user_id,
                    'title' => 'تمت الموافقة على حسابك!',
                    'message' => 'لقد وافق الأدمن على طلبك. يمكنك الآن الوصول إلى لوحة تحكم ' . $req->requested_role,
                    'type' => 'role_approved',
                    'is_read' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                // A rejected user loses any API tokens they were issued while pending.
                DB::table('personal_access_tokens')->where('tokenable_type', User::class)->where('tokenable_id', $req->user_id)->delete();
            }

            return true;
        });
    }
}
