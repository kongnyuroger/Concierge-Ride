import { describe, it, expect, vi, afterEach } from 'vitest';

vi.mock('$env/static/public', () => ({ PUBLIC_API_URL: 'http://localhost:8000' }));

const { fetchHealth } = await import('./api');

describe('fetchHealth', () => {
	afterEach(() => {
		vi.unstubAllGlobals();
	});

	it('returns the parsed status on success', async () => {
		vi.stubGlobal(
			'fetch',
			vi.fn(async () => new Response(JSON.stringify({ status: 'ok' }), { status: 200 }))
		);

		await expect(fetchHealth()).resolves.toEqual({ status: 'ok' });
	});

	it('throws when the API responds with an error status', async () => {
		vi.stubGlobal(
			'fetch',
			vi.fn(async () => new Response('', { status: 500 }))
		);

		await expect(fetchHealth()).rejects.toThrow('API responded with 500');
	});
});
