<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\TelegramGroup;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display the list of users and technicians with their Telegram Group assignments.
     */
    public function index(): View
    {
        $users = User::query()
            ->with('telegramGroup')
            ->withCount('psbOrders')
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        $telegramGroups = TelegramGroup::query()
            ->orderByDesc('is_all_team')
            ->orderBy('nama_grup')
            ->get();

        return view('admin.users.index', [
            'users' => $users,
            'telegramGroups' => $telegramGroups,
        ]);
    }

    /**
     * Store a newly created User.
     */
    public function store(UserRequest $request): RedirectResponse
    {
        $user = User::create($request->validated());

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User {$user->name} ({$user->username}) berhasil ditambahkan.");
    }

    /**
     * Update an existing User (keeping password unchanged when left blank).
     */
    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User {$user->name} ({$user->username}) berhasil diperbarui.");
    }

    /**
     * Delete a User if not self and not referenced by PSB orders.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->id === $user->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($user->psbOrders()->exists()) {
            return back()->with(
                'error',
                "User {$user->name} memiliki riwayat PSB Order. Nonaktifkan akun (is_active = false) sebagai gantinya."
            );
        }

        $nama = $user->name;
        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User {$nama} berhasil dihapus.");
    }
}
