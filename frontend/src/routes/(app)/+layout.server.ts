import { redirect } from '@sveltejs/kit';
import { AUTH_COOKIE_NAME } from '$lib/auth';
import type { LayoutServerLoad } from './$types';

// Guards every route in this (app) group. Refuse-by-default: no token
// cookie, no access — future protected pages just live under this group.
export const load: LayoutServerLoad = async ({ cookies }) => {
	const token = cookies.get(AUTH_COOKIE_NAME);

	if (!token) {
		redirect(303, '/login');
	}
};
