/**
 * CR-13: price-book entry shape (mirrors PriceBookController's JSON) and the
 * page's display strings.
 *
 * `labels` is the i18n seam — every user-facing string on the price-book
 * page reads from here rather than being written inline in the markup, so
 * CR-11 can later swap this object for a real translation lookup (e.g.
 * `t('priceBook.edit')`) without touching the page's logic.
 */

export type PriceBookEntry = {
	id: number;
	city_id: number;
	product_id: number;
	tier_id: number;
	customer_price: number;
	included_hours: number | null;
	included_distance_km: number | null;
	overage_rate_per_hour: number | null;
	overage_rate_per_km: number | null;
	margin_floor: number;
	status: string;
	city: { id: number; name: string; code: string };
	product: { id: number; name: string; code: string };
	tier: { id: number; name: string; code: string };
};

export const labels = {
	pageTitle: 'Price book',
	pageDescription:
		'Prices by city, product, and tier. Editing an entry only affects new jobs — it never changes a price already captured on an existing job.',
	table: {
		city: 'City',
		product: 'Product',
		tier: 'Tier',
		customerPrice: 'Customer price (XAF)',
		includedHours: 'Included hours',
		includedDistanceKm: 'Included distance (km)',
		overageRatePerHour: 'Overage / hour (XAF)',
		overageRatePerKm: 'Overage / km (XAF)',
		marginFloor: 'Margin floor (XAF)',
		actions: 'Actions'
	},
	edit: 'Edit',
	save: 'Save',
	cancel: 'Cancel',
	notSet: '—',
	loadError: 'Could not load the price book.',
	saveError: 'Could not save this entry. Check the values and try again.',
	empty: 'No price book entries yet.'
};
