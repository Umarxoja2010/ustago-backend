<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Http\Resources\Admin\AdminUserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with('masterProfile');

        if ($request->filled('role')) {
            $query->where('role', $request->string('role'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%"));
        }

        $users = $query->latest()->paginate($request->integer('perPage', 20))->withQueryString();

        return $this->ok([
            'items' => AdminUserResource::collection($users->items()),
            'meta' => [
                'page' => $users->currentPage(),
                'perPage' => $users->perPage(),
                'total' => $users->total(),
                'lastPage' => $users->lastPage(),
            ],
        ]);
    }

    public function show(User $user): JsonResponse
    {
        return $this->ok(new AdminUserResource($user->load('masterProfile')));
    }

    /** Suspend/reactivate a user. Admin accounts can't be touched here to avoid accidental lockout. */
    public function updateStatus(UpdateUserStatusRequest $request, User $user): JsonResponse
    {
        if ($user->isAdmin()) {
            return $this->fail('errors.cannotModifyAdmin', [], 403);
        }

        $user->update(['status' => $request->validated()['status']]);

        return $this->ok(new AdminUserResource($user->fresh('masterProfile')), 'User updated');
    }
}
