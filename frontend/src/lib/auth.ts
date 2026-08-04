import { goto } from '$app/navigation';
import { resolve } from '$app/paths';
import { browser } from '$app/environment';
import { PUBLIC_API_URL } from '$env/static/public';

export const AUTH_COOKIE_NAME = 'concierge_ride_token';

export type AuthUser = {
	id: number;
	name: string;
	email: string;
};

function setTokenCookie(token: string) {
	const secure = location.protocol === 'https:' ? '; Secure' : '';
	// 12h — matches how long a dispatcher's shift-long session should last
	// without needing to sign in again; not a security boundary on its own,
	// the server can revoke the token independently at any time.
	document.cookie = `${AUTH_COOKIE_NAME}=${token}; path=/; max-age=43200; SameSite=Lax${secure}`;
}

function clearTokenCookie() {
	document.cookie = `${AUTH_COOKIE_NAME}=; path=/; max-age=0; SameSite=Lax`;
}

export function getToken(): string | null {
	if (!browser) return null;
	const match = document.cookie.match(new RegExp(`(?:^|; )${AUTH_COOKIE_NAME}=([^;]*)`));
	return match ? decodeURIComponent(match[1]) : null;
}

export async function login(email: string, password: string): Promise<AuthUser> {
	const response = await fetch(`${PUBLIC_API_URL}/api/auth/login`, {
		method: 'POST',
		headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
		body: JSON.stringify({ email, password })
	});

	if (!response.ok) {
		throw new Error('Invalid email or password.');
	}

	const data = (await response.json()) as { token: string; user: AuthUser };
	setTokenCookie(data.token);
	return data.user;
}

export async function logout(): Promise<void> {
	const token = getToken();
	clearTokenCookie();

	if (token) {
		await fetch(`${PUBLIC_API_URL}/api/auth/logout`, {
			method: 'POST',
			headers: { Authorization: `Bearer ${token}`, Accept: 'application/json' }
		}).catch(() => {
			// Best-effort server-side revocation — the cookie is already
			// cleared client-side either way.
		});
	}

	await goto(resolve('/login'));
}

/** Fetch wrapper that attaches the bearer token and redirects to /login on 401. */
export async function apiFetch(path: string, options: RequestInit = {}): Promise<Response> {
	const token = getToken();
	const headers = new Headers(options.headers);
	headers.set('Accept', 'application/json');
	if (token) headers.set('Authorization', `Bearer ${token}`);

	const response = await fetch(`${PUBLIC_API_URL}${path}`, { ...options, headers });

	if (response.status === 401 && browser) {
		clearTokenCookie();
		await goto(resolve('/login'));
	}

	return response;
}
