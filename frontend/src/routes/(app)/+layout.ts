import { apiFetch } from '$lib/auth';
import type { AuthUser } from '$lib/auth';
import type { LayoutLoad } from './$types';

// Client-side only (apiFetch's token lives in a plain cookie read via
// document.cookie, unavailable during SSR) — so the nav briefly renders
// with no gated items until this resolves, then updates. Acceptable here:
// this is UX only, not the enforcement boundary (see lib/permissions.ts).
export const load: LayoutLoad = async () => {
	const response = await apiFetch('/api/user');

	if (!response.ok) {
		return { user: null as AuthUser | null };
	}

	const user = (await response.json()) as AuthUser;
	return { user };
};
