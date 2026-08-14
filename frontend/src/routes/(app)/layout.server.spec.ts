import { describe, it, expect } from 'vitest';
import { isRedirect } from '@sveltejs/kit';
import { AUTH_COOKIE_NAME } from '$lib/auth';
import { load } from './+layout.server';
import type { LayoutServerLoadEvent } from './$types';

// Regression test: a prior version of this file replaced the guard with a
// no-op, which let an unauthenticated visitor load the whole dashboard
// shell directly. This asserts the (app) route group is refuse-by-default.
function eventWithToken(token: string | undefined) {
	return {
		cookies: { get: (name: string) => (name === AUTH_COOKIE_NAME ? token : undefined) }
	} as unknown as LayoutServerLoadEvent;
}

describe('(app) layout guard', () => {
	it('redirects to /login when there is no auth cookie', async () => {
		try {
			await load(eventWithToken(undefined));
			expect.unreachable('expected load() to throw a redirect');
		} catch (e) {
			expect(isRedirect(e)).toBe(true);
			if (isRedirect(e)) {
				expect(e.status).toBe(303);
				expect(e.location).toBe('/login');
			}
		}
	});

	it('does not redirect when an auth cookie is present', async () => {
		await expect(load(eventWithToken('some-token'))).resolves.toBeUndefined();
	});
});
