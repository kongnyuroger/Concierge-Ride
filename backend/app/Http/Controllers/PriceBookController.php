<?php

namespace App\Http\Controllers;

use App\Models\PriceBookEntry;
use Illuminate\Http\Request;

class PriceBookController extends Controller
{
    public function index()
    {
        $entries = PriceBookEntry::with(['city', 'product', 'tier'])
            ->where('status', 'active')
            ->orderBy('city_id')
            ->orderBy('product_id')
            ->orderBy('tier_id')
            ->get();

        return response()->json(['data' => $entries]);
    }

    /**
     * "Edit" a price-book entry — see PriceBookEntry::createNewVersion().
     * The (city, product, tier) key is fixed to the target entry's own key;
     * only the value fields are editable here. Changing which combination
     * an entry represents isn't an edit, it's a different entry.
     */
    public function update(Request $request, PriceBookEntry $priceBookEntry)
    {
        // included_* and overage_rate_per_* are set-together-or-not-at-all
        // pairs (mirrors the DB CHECK constraint) — required_with runs both
        // directions so neither can be sent without the other.
        $validated = $request->validate([
            'customer_price' => ['required', 'integer', 'min:0'],
            'included_hours' => ['nullable', 'integer', 'min:0', 'required_with:overage_rate_per_hour'],
            'included_distance_km' => ['nullable', 'integer', 'min:0', 'required_with:overage_rate_per_km'],
            'overage_rate_per_hour' => ['nullable', 'integer', 'min:0', 'required_with:included_hours'],
            'overage_rate_per_km' => ['nullable', 'integer', 'min:0', 'required_with:included_distance_km'],
            'margin_floor' => ['required', 'integer', 'min:0'],
        ]);

        $newEntry = PriceBookEntry::createNewVersion([
            'city_id' => $priceBookEntry->city_id,
            'product_id' => $priceBookEntry->product_id,
            'tier_id' => $priceBookEntry->tier_id,
            ...$validated,
        ]);

        return response()->json(['data' => $newEntry->load(['city', 'product', 'tier'])]);
    }
}
