<?php

namespace App\Http\Controllers;

use App\Models\AmenityItem;
use App\Models\FoodItem;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Owner-managed price lists: the room-service menu (food items) and the
 * amenity list (towels, kettle, slippers, plates...) with replacement prices.
 */
class CatalogController extends Controller
{
    private const KINDS = [
        'food' => ['model' => FoodItem::class, 'table' => 'food_items', 'price' => 'price', 'title' => 'Room service menu', 'noun' => 'Menu item', 'label' => 'Price'],
        'amenities' => ['model' => AmenityItem::class, 'table' => 'amenity_items', 'price' => 'replacement_price', 'title' => 'Room amenities', 'noun' => 'Amenity', 'label' => 'Replacement price (if missing)'],
    ];

    public function index(Request $request, string $kind)
    {
        $cfg = $this->config($kind);
        $model = $cfg['model'];

        return view('catalog.index', [
            'kind' => $kind,
            'cfg' => $cfg,
            'items' => $model::orderBy('name')->get(),
            'editing' => $request->filled('edit') ? $model::find($request->query('edit')) : null,
        ]);
    }

    public function store(Request $request, string $kind)
    {
        $cfg = $this->config($kind);
        $cfg['model']::create($this->validated($request, $cfg));

        return redirect()->route('catalog.index', $kind)->with('status', $cfg['noun'].' added.');
    }

    public function update(Request $request, string $kind, int $id)
    {
        $cfg = $this->config($kind);
        $item = $cfg['model']::findOrFail($id);
        $item->update($this->validated($request, $cfg, $item->id) + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('catalog.index', $kind)->with('status', $cfg['noun'].' updated.');
    }

    public function destroy(string $kind, int $id)
    {
        $cfg = $this->config($kind);
        $cfg['model']::findOrFail($id)->update(['is_active' => false]);

        return back()->with('status', 'Item hidden from new orders. Past bills are unchanged.');
    }

    private function config(string $kind): array
    {
        abort_unless(isset(self::KINDS[$kind]), 404);

        return self::KINDS[$kind];
    }

    private function validated(Request $request, array $cfg, ?int $ignore = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique($cfg['table'])
                ->where('hotel_id', $request->user()->hotel_id)->ignore($ignore)],
            'price' => ['required', 'numeric', 'min:0'],
        ]);

        return ['name' => $data['name'], $cfg['price'] => $data['price']];
    }
}
