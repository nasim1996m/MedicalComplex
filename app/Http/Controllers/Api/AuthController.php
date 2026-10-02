<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Support\Accounts;
use App\Support\GoogleIdentity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AuthController extends ApiController
{
    /**
     * Google login: verifies an ID token ("token") or an access token ("access_token")
     * issued to this app's OAuth client, then issues a Sanctum API token.
     */
    public function googleLogin(Request $request)
    {
        $request->validate([
            'token' => 'required_without:access_token|nullable|string|max:4096',
            'access_token' => 'required_without:token|nullable|string|max:4096',
        ]);

        if (!GoogleIdentity::clientId()) {
            return $this->error('تسجيل الدخول بـ Google غير مُعدّ على الخادم', 503);
        }

        $identity = $request->filled('token')
            ? GoogleIdentity::fromIdToken($request->token)
            : GoogleIdentity::fromAccessToken($request->access_token);

        if (!$identity) {
            return $this->error('توكن Google غير صالح أو انتهت صلاحيته', 401);
        }

        $user = Accounts::fromGoogle($identity);
        if ($user->status === 'rejected') {
            return $this->error('تم رفض طلب انضمام هذا الحساب', 403);
        }

        $token = $user->createToken('google-auth')->plainTextToken;

        return $this->success([
            'user' => $this->publicUser($user),
            'token' => $token,
            'pending_request' => DB::table('role_requests')->where('user_id', $user->id)->where('status', 'pending')->first(),
        ], 'تم تسجيل الدخول بنجاح عبر حساب Google');
    }

    public function me(Request $request)
    {
        return $this->success($this->publicUser($request->user()));
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'تم تسجيل الخروج');
    }

    /** The signed-in user (never another user) asks the admin for a role. */
    public function requestRole(Request $request)
    {
        $data = $request->validate([
            'requested_role' => ['required', Rule::in(User::REQUESTABLE_ROLES)],
            'requested_specialty' => 'nullable|string|max:150',
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();
        if ($user->isApproved()) {
            return $this->error('حسابك مفعّل مسبقاً', 409);
        }

        $requestId = Accounts::submitRoleRequest($user, $data['requested_role'], $data['requested_specialty'] ?? null, $data['notes'] ?? null);

        return $this->success([
            'request_id' => $requestId,
            'user' => $this->publicUser($user->fresh()),
        ], 'تم إرسال طلب الانضمام إلى الأدمن بنجاح وهو قيد المراجعة.');
    }

    public function listRoleRequests()
    {
        $requests = DB::table('role_requests')
            ->join('users', 'role_requests.user_id', '=', 'users.id')
            ->select('role_requests.*', 'users.name as user_name', 'users.email as user_email', 'users.avatar as user_avatar')
            ->where('role_requests.status', 'pending')
            ->orderBy('role_requests.created_at', 'desc')
            ->get();

        return $this->success($requests, 'قائمة طلبات الأدوار المعلقة');
    }

    public function approveRoleRequest(Request $request, $id)
    {
        if (!Accounts::review((int) $id, $request->user(), true)) {
            return $this->error('الطلب غير موجود أو تمت مراجعته مسبقاً', 404);
        }

        return $this->success(null, 'تمت الموافقة على الطلب وتفعيل الحساب بنجاح.');
    }

    public function rejectRoleRequest(Request $request, $id)
    {
        if (!Accounts::review((int) $id, $request->user(), false)) {
            return $this->error('الطلب غير موجود أو تمت مراجعته مسبقاً', 404);
        }

        return $this->success(null, 'تم رفض الطلب.');
    }

    private function publicUser(User $user): array
    {
        return $user->only(['id', 'name', 'email', 'avatar', 'role', 'specialty', 'status', 'phone']);
    }
}
