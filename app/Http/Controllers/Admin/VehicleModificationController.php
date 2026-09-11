<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VehicleModificationRequest;
use App\Models\VehicleModification;
use App\Support\AdminTable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleModificationController extends Controller
{
    public function index(Request $request): Response
    {
        $table = AdminTable::for(VehicleModification::query(), $request)
            ->searchable(['name', 'vendor', 'purchased_from', 'description'])
            ->sortable(['name', 'vendor', 'cost', 'install_date', 'purchase_date', 'created_at'], 'install_date');

        return Inertia::render('admin/vehicle-modifications/Index', [
            'modifications' => $table->paginate()->through(fn (VehicleModification $mod) => [
                'id' => $mod->id,
                'name' => $mod->name,
                'vendor' => $mod->vendor,
                'purchased_from' => $mod->purchased_from,
                // Stored as integer cents, presented as dollars.
                'cost' => $mod->cost === null ? null : $mod->cost / 100,
                'purchase_date' => $mod->purchase_date,
                'install_date' => $mod->install_date,
                'shown_on_timeline' => (bool) $mod->shown_on_timeline,
                'url' => $mod->url,
            ]),
            'filters' => $table->state(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/vehicle-modifications/Edit', ['modification' => null]);
    }

    public function store(VehicleModificationRequest $request): RedirectResponse
    {
        $mod = VehicleModification::create($this->payload($request));

        return redirect()
            ->route('admin.vehicle-modifications.index')
            ->with('success', "\"{$mod->name}\" created.");
    }

    public function edit(VehicleModification $vehicleModification): Response
    {
        return Inertia::render('admin/vehicle-modifications/Edit', [
            'modification' => [
                'id' => $vehicleModification->id,
                'name' => $vehicleModification->name,
                'vendor' => $vehicleModification->vendor,
                'purchased_from' => $vehicleModification->purchased_from,
                'description' => $vehicleModification->description,
                'purchase_date' => $vehicleModification->purchase_date,
                'install_date' => $vehicleModification->install_date,
                'cost' => $vehicleModification->cost === null
                    ? null
                    : number_format($vehicleModification->cost / 100, 2, '.', ''),
                'url' => $vehicleModification->url,
                'shown_on_timeline' => (bool) $vehicleModification->shown_on_timeline,
            ],
        ]);
    }

    public function update(VehicleModificationRequest $request, VehicleModification $vehicleModification): RedirectResponse
    {
        $vehicleModification->update($this->payload($request));

        return redirect()
            ->route('admin.vehicle-modifications.index')
            ->with('success', 'Modification updated.');
    }

    public function destroy(VehicleModification $vehicleModification): RedirectResponse
    {
        $vehicleModification->delete();

        return redirect()
            ->route('admin.vehicle-modifications.index')
            ->with('success', "\"{$vehicleModification->name}\" deleted.");
    }

    /**
     * Convert the dollar input back into the integer cents the column holds.
     *
     * @return array<string, mixed>
     */
    protected function payload(VehicleModificationRequest $request): array
    {
        $data = $request->validated();

        $data['cost'] = isset($data['cost']) && $data['cost'] !== null
            ? (int) round(((float) $data['cost']) * 100)
            : null;

        return $data;
    }
}
