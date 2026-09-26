<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Http;

class AuthController extends ApiController
{
    /**
     * Google OAuth Login — verifies the Google token, then issues a Sanctum token.
     * Accepts either an ID token (credential) or an access_token.
     */
    public function googleLogin(Request $request)
    {
        $request->validate([
            'token'        => 'nullable|string',
            'access_token' => 'nullable|string',
            'email'        => 'nullable|email',
            'name'         => 'nullable|string',
            'google_id'    => 'nullable|string',
            'avatar'       => 'nullable|string',
        ]);

        $email    = null;
        $name     = null;
        $googleId = null;
        $avatar   = null;

        // ─── Path A: ID Token (credential) ─────────────────────────────────
        if ($request->filled('token')) {
            try {
                $client = new GoogleClient(['client_id' => env('GOOGLE_CLIENT_ID')]);
                $payload = $client->verifyIdToken($request->token);

                if (!$payload) {
                    return $this->error('توكن Google غير صالح', 401);
                }

                $email    = $payload['email'];
                $name     = $payload['name'] ?? 'مستخدم Google';
                $googleId = $payload['sub'];
                $avatar   = $payload['picture'] ?? null;
            } catch (\Exception $e) {
                return $this->error('فشل التحقق من التوكن: ' . $e->getMessage(), 401);
            }
        }
        // ─── Path B: Access Token → Fetch user info directly from Google ───
        elseif ($request->filled('access_token')) {
            // جلب بيانات المستخدم مباشرة من جوجل باستخدام الـ access_token
            $userInfoResponse = Http::withToken($request->access_token)
                ->get('https://www.googleapis.com/oauth2/v3/userinfo');

            if ($userInfoResponse->failed()) {
                return $this->error('توكن Google غير صالح أو انتهت صلاحيته', 401);
            }

            $userInfo = $userInfoResponse->json();

            $email    = $userInfo['email'] ?? null;
            $name     = $userInfo['name'] ?? 'مستخدم Google';
            $googleId = $userInfo['sub'] ?? null;
            $avatar   = $userInfo['picture'] ?? null;

            if (!$email) {
                return $this->error('تعذر الحصول على البريد الإلكتروني من حساب Google', 422);
            }
        } else {
            return $this->error('يجب إرسال token أو access_token', 422);
        }

        // ─── Find or Create user ────────────────────────────────────────────
        $user = DB::table('users')->where('email', $email)->first();

        if (!$user) {
            $userId = DB::table('users')->insertGetId([
                'name'       => $name,
                'email'      => $email,
                'google_id'  => $googleId,
                'avatar'     => $avatar ?? 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=4f46e5&color=fff',
                'password'   => Hash::make(Str::random(24)),
                'role'       => 'pending',
                'status'     => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $user = DB::table('users')->where('id', $userId)->first();

            // إضافة طلب دور أولي تلقائياً ليصل للأدمن فوراً
            DB::table('role_requests')->insert([
                'user_id'             => $userId,
                'requested_role'      => 'doctor',
                'requested_specialty' => 'طبيب عام (بانتظار تحديد التخصص)',
                'notes'               => 'تسجيل عبر Google وبانتظار موافقة وتفعيل الأدمن',
                'status'              => 'pending',
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            DB::table('notifications')->insert([
                'user_id'     => null,
                'target_role' => 'admin',
                'title'       => 'طلب انضمام جديد',
                'message'     => 'سجل ' . $name . ' (' . $email . ') بحساب Google ويطلب الانضمام وتفعيل الداشبورد',
                'type'        => 'role_request',
                'is_read'     => 0,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        } else {
            DB::table('users')->where('id', $user->id)->update([
                'google_id'  => $user->google_id ?: $googleId,
                'avatar'     => $user->avatar    ?: $avatar,
                'updated_at' => now(),
            ]);
            $user = DB::table('users')->where('id', $user->id)->first();
        }

        // ─── التحقق من وجود طلب دور معلق لهذا المستخدم ───
        $existingRequest = DB::table('role_requests')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        // ─── Issue Sanctum token ────────────────────────────────────────────
        $tokenName   = 'google-auth-' . Str::random(8);
        $plainToken  = Str::random(64);
        $hashedToken = hash('sha256', $plainToken);

        DB::table('personal_access_tokens')->insert([
            'tokenable_type' => 'App\\Models\\User',
            'tokenable_id'   => $user->id,
            'name'           => $tokenName,
            'token'          => $hashedToken,
            'abilities'      => '["*"]',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return $this->success([
            'user'            => $user,
            'token'           => $plainToken,
            'pending_request' => $existingRequest,
        ], 'تم تسجيل الدخول بنجاح عبر حساب Google');
    }

    /**
     * Submit role request to Admin
     */
    public function requestRole(Request $request)
    {
        $request->validate([
            'user_id'             => 'required|exists:users,id',
            'requested_role'      => 'required|string',
            'requested_specialty' => 'nullable|string',
            'notes'               => 'nullable|string',
        ]);

        $user = DB::table('users')->where('id', $request->user_id)->first();
        if (!$user) {
            return $this->error('المستخدم غير موجود', 404);
        }

        // Check if there is already a pending request
        $existing = DB::table('role_requests')
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            DB::table('role_requests')->where('id', $existing->id)->update([
                'requested_role'      => $request->requested_role,
                'requested_specialty' => $request->requested_specialty,
                'notes'               => $request->notes,
                'updated_at'          => now(),
            ]);
            $requestId = $existing->id;
        } else {
            $requestId = DB::table('role_requests')->insertGetId([
                'user_id'             => $user->id,
                'requested_role'      => $request->requested_role,
                'requested_specialty' => $request->requested_specialty,
                'notes'               => $request->notes,
                'status'              => 'pending',
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }

        // Make sure user role & status are pending
        DB::table('users')->where('id', $user->id)->update([
            'role'       => 'pending',
            'status'     => 'pending',
            'updated_at' => now(),
        ]);

        // Create notification for Admin
        DB::table('notifications')->insert([
            'user_id'     => null,
            'target_role' => 'admin',
            'title'       => 'طلب انضمام جديد',
            'message'     => 'قدم ' . $user->name . ' (' . $user->email . ') طلب فتح داشبورد ' . $request->requested_role,
            'type'        => 'role_request',
            'is_read'     => 0,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return $this->success([
            'request_id' => $requestId,
            'user'       => DB::table('users')->where('id', $user->id)->first(),
        ], 'تم إرسال طلب الانضمام إلى الأدمن بنجاح وهو قيد المراجعة.');
    }

    public function listRoleRequests()
    {
        // مزامنة أي مستخدم pending بدون سجل في role_requests
        $pendingUsersWithoutRequest = DB::table('users')
            ->where(function ($q) {
                $q->where('role', 'pending')->orWhere('status', 'pending');
            })
            ->whereNotIn('id', function ($sub) {
                $sub->select('user_id')->from('role_requests');
            })
            ->get();

        foreach ($pendingUsersWithoutRequest as $pu) {
            DB::table('role_requests')->insert([
                'user_id'             => $pu->id,
                'requested_role'      => 'doctor',
                'requested_specialty' => $pu->specialty ?: 'طبيب عام (بانتظار تحديد التخصص)',
                'notes'               => 'تسجيل عبر Google وبانتظار موافقة وتفعيل الأدمن',
                'status'              => 'pending',
                'created_at'          => $pu->created_at ?: now(),
                'updated_at'          => now(),
            ]);
        }

        $requests = DB::table('role_requests')
            ->join('users', 'role_requests.user_id', '=', 'users.id')
            ->select('role_requests.*', 'users.name as user_name', 'users.email as user_email', 'users.avatar as user_avatar')
            ->where('role_requests.status', 'pending')
            ->orderBy('role_requests.created_at', 'desc')
            ->get();

        return $this->success($requests, 'قائمة طلبات الأدوار المعلقة');
    }

    /**
     * Approve user role request
     */
    public function approveRoleRequest(Request $request, $id)
    {
        $roleRequest = DB::table('role_requests')->where('id', $id)->first();
        if (!$roleRequest) {
            return $this->error('الطلب غير موجود', 404);
        }

        $adminId = $request->admin_id ?? DB::table('users')->where('role', 'admin')->value('id') ?? 1;

        DB::table('role_requests')->where('id', $id)->update([
            'status'      => 'approved',
            'reviewed_by' => $adminId,
            'updated_at'  => now(),
        ]);

        DB::table('users')->where('id', $roleRequest->user_id)->update([
            'role'       => $roleRequest->requested_role,
            'specialty'  => $roleRequest->requested_specialty,
            'status'     => 'approved',
            'updated_at' => now(),
        ]);

        DB::table('notifications')->insert([
            'user_id'     => $roleRequest->user_id,
            'title'       => 'تمت الموافقة على حسابك!',
            'message'     => 'لقد وافق الأدمن على طلبك. يمكنك الآن الوصول إلى لوحة تحكم ' . $roleRequest->requested_role,
            'type'        => 'role_approved',
            'is_read'     => 0,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return $this->success([
            'user' => DB::table('users')->where('id', $roleRequest->user_id)->first(),
        ], 'تمت الموافقة على الطلب وتفعيل الحساب بنجاح.');
    }

    /**
     * Reject user role request
     */
    public function rejectRoleRequest(Request $request, $id)
    {
        $roleRequest = DB::table('role_requests')->where('id', $id)->first();
        if (!$roleRequest) {
            return $this->error('الطلب غير موجود', 404);
        }

        $adminId = $request->admin_id ?? DB::table('users')->where('role', 'admin')->value('id') ?? 1;

        DB::table('role_requests')->where('id', $id)->update([
            'status'      => 'rejected',
            'reviewed_by' => $adminId,
            'updated_at'  => now(),
        ]);

        DB::table('users')->where('id', $roleRequest->user_id)->update([
            'status'     => 'rejected',
            'updated_at' => now(),
        ]);

        return $this->success(null, 'تم رفض الطلب.');
    }
}
