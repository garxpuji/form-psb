<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TelegramGroupRequest;
use App\Models\TelegramGroup;
use App\Services\TelegramService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TelegramGroupController extends Controller
{
    /**
     * Display the list of Telegram groups.
     */
    public function index(): View
    {
        $groups = TelegramGroup::query()
            ->withCount('users')
            ->orderByDesc('is_all_team')
            ->orderBy('nama_grup')
            ->get();

        return view('admin.telegram-groups.index', [
            'groups' => $groups,
        ]);
    }

    public function testConnection(TelegramService $telegram): RedirectResponse
    {
        return redirect()
            ->route('admin.telegram-groups.index')
            ->with('connection_test', $telegram->testConnection());
    }

    /**
     * Store a newly created Telegram group (enforcing strictly one is_all_team = true record).
     */
    public function store(TelegramGroupRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated): void {
            if ($validated['is_all_team']) {
                TelegramGroup::where('is_all_team', true)->update(['is_all_team' => false]);
            }

            TelegramGroup::create($validated);
        });

        return redirect()
            ->route('admin.telegram-groups.index')
            ->with('success', "Grup Telegram {$validated['nama_grup']} berhasil ditambahkan.");
    }

    /**
     * Update an existing Telegram group (enforcing strictly one is_all_team = true record).
     */
    public function update(TelegramGroupRequest $request, TelegramGroup $telegramGroup): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($telegramGroup, $validated): void {
            if ($validated['is_all_team']) {
                TelegramGroup::where('id', '!=', $telegramGroup->id)
                    ->where('is_all_team', true)
                    ->update(['is_all_team' => false]);
            }

            $telegramGroup->update($validated);
        });

        return redirect()
            ->route('admin.telegram-groups.index')
            ->with('success', "Grup Telegram {$telegramGroup->nama_grup} berhasil diperbarui.");
    }

    /**
     * Delete a Telegram group (users' telegram_group_id will nullOnDelete).
     */
    public function destroy(TelegramGroup $telegramGroup): RedirectResponse
    {
        if ($telegramGroup->is_all_team) {
            return back()->with(
                'error',
                'Grup utama ALL TEAM PSB tidak boleh dihapus karena diperlukan sebagai fallback non-tim.'
            );
        }

        $nama = $telegramGroup->nama_grup;
        $telegramGroup->delete();

        return redirect()
            ->route('admin.telegram-groups.index')
            ->with('success', "Grup Telegram {$nama} berhasil dihapus.");
    }
}
