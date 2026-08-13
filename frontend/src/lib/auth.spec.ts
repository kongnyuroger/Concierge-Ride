import { describe, it, expect, vi, afterEach, beforeEach } from 'vitest';

vi.mock('$env/static/public', () => ({ PUBLIC_API_URL: 'http://localhost:8000' }));
vi.mock('$app/environment', () => ({ browser: true }));
vi.mock('$app/navigation', () => ({ goto: vi.fn() }));

const { login, getToken, apiFetch, AUTH_COOKIE_NAME } = await import('./auth');

// Minimal fake cookie jar — real document.cookie merges/replaces individual
// cookies on write and concatenates all of them on read; a plain string stub
// can't express that, so this fakes just enough of the real semantics.
function fakeCookieJar() {
	const store = new Map<string, string>();
	return {
		get cookie() {
			return [...store.entries()].map(([k, v]) => `${k}=${v}`).join('; ');
		},
		set cookie(entry: string) {
			const [pair] = entry.split(';');
			const [key, value] = pair.split('=');
			if (entry.includes('max-age=0')) {
				store.delete(key);
			} else {
				store.set(key, value);
			}
		}
	};
}

describe('auth', () => {
	beforeEach(() => {
		vi.stubGlobal('document', fakeCookieJar());
		vi.stubGlobal('location', { protocol: 'http:' });
	});

	afterEach(() => {
		vi.unstubAllGlobals();
	});

	it('login stores the token from a successful response', async () => {
		const apiUser = {
			id: 1,
			name: 'Owner',
			email: 'o@x.test',
			role: 'owner',
			permissions: ['leads.manage']
		};
		vi.stubGlobal(
			'fetch',
			vi.fn(async () => Response.json({ token: 'abc123', user: apiUser }))
		);

		const user = await login('o@x.test', 'secret');

		expect(user).toEqual(apiUser);
		expect(getToken()).toBe('abc123');
	});

	it('login throws on a failed response and stores no token', async () => {
		vi.stubGlobal(
			'fetch',
			vi.fn(async () => new Response('', { status: 422 }))
		);

		await expect(login('o@x.test', 'wrong')).rejects.toThrow();
		expect(getToken()).toBeNull();
	});

	it('apiFetch attaches the bearer token when one is set', async () => {
		document.cookie = `${AUTH_COOKIE_NAME}=abc123`;
		const fetchMock = vi.fn(async (_url: string, options?: RequestInit) => {
			expect((options?.headers as Headers).get('Authorization')).toBe('Bearer abc123');
			return new Response('{}', { status: 200 });
		});
		vi.stubGlobal('fetch', fetchMock);

		await apiFetch('/api/user');

		expect(fetchMock).toHaveBeenCalledOnce();
	});
});
