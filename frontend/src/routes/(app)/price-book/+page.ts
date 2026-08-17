import { apiFetch } from '$lib/auth';
import type { PriceBookEntry } from '$lib/priceBook';
import type { PageLoad } from './$types';

// Client-side only — same reasoning as the shared layout's load (apiFetch's
// token cookie isn't available during SSR). A dispatcher/account-manager
// landing here directly (nav hides the link, but the URL is still typable)
// gets a non-ok response from the server's `permission:` middleware — this
// just surfaces that as loadError, it doesn't add a second access check.
export const load: PageLoad = async () => {
	const response = await apiFetch('/api/price-book');

	if (!response.ok) {
		return { entries: [] as PriceBookEntry[], loadError: true };
	}

	const body = (await response.json()) as { data: PriceBookEntry[] };
	return { entries: body.data, loadError: false };
};
