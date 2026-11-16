<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Models\RouterIpSlot;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SlotMonitorController extends Controller
{
    /**
     * Display the Slot Monitor page with per-router available/used counts and slot inspection table.
     */
    public function index(Request $request): View
    {
        $selectedRouterId = trim((string) $request->query('router_id', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $search = trim((string) $request->query('q', ''));

        $distributionRouters = Router::query()
            ->where('is_pppoe_server', false)
            ->with('sales')
            ->withCount([
                'ipSlots as available_slots_count' => fn ($q) => $q->where('status', 'available'),
                'ipSlots as used_slots_count' => fn ($q) => $q->where('status', 'used'),
            ])
            ->orderBy('nama_router')
            ->get();

        $slotsQuery = RouterIpSlot::query()
            ->with(['router', 'psbOrder'])
            ->orderBy('router_id')
            ->orderBy('id');

        if ($selectedRouterId !== '' && is_numeric($selectedRouterId)) {
            $slotsQuery->where('router_id', (int) $selectedRouterId);
        }

        if (in_array($statusFilter, ['available', 'used'], true)) {
            $slotsQuery->where('status', $statusFilter);
        }

        if ($search !== '') {
            $slotsQuery->where(function ($q) use ($search): void {
                $q->where('ip_address', 'like', "%{$search}%")
                    ->orWhere('queue_name', 'like', "%{$search}%")
                    ->orWhere('queue_mk_id', 'like', "%{$search}%");
            });
        }

        $slots = $slotsQuery->paginate(25)->withQueryString();

        return view('admin.slots.index', [
            'distributionRouters' => $distributionRouters,
            'slots' => $slots,
            'filters' => [
                'router_id' => $selectedRouterId,
                'status' => $statusFilter,
                'q' => $search,
            ],
        ]);
    }
}
