import { PUBLIC_API_URL } from '$env/static/public';

export async function fetchHealth(): Promise<{ status: string }> {
	const response = await fetch(`${PUBLIC_API_URL}/api/health`);
	if (!response.ok) {
		throw new Error(`API responded with ${response.status}`);
	}
	return response.json();
}
