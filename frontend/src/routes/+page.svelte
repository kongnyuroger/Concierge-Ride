<script lang="ts">
	import { fetchHealth } from '$lib/api';

	const health = fetchHealth();
</script>

<h1 class="text-2xl font-bold">Concierge Ride</h1>

<p class="mt-4">
	API health check:
	{#await health}
		checking…
	{:then data}
		<span class="font-mono text-green-600">{data.status}</span>
	{:catch error}
		<span class="font-mono text-red-600">error: {error.message}</span>
	{/await}
</p>
