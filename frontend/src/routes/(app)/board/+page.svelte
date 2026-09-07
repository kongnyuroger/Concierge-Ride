<script lang="ts">
	import { visibleNavItems } from '$lib/permissions';
	import type { LayoutProps } from './$types';

	let { data }: LayoutProps = $props();
	let navItems = $derived(visibleNavItems(data.user?.permissions ?? []));
</script>

<div class="min-h-screen bg-gray-50">
	<header class="flex items-center justify-between border-b bg-white px-6 py-3">
		<nav class="flex gap-4 text-sm">
			{#each navItems as item (item.href)}
				<a href={item.href} class="text-gray-700 hover:underline">{item.label}</a>
			{/each}
		</nav>

		<div class="flex items-center gap-3 text-sm">
			{#if data.user}
				<span class="text-gray-500">{data.user.name} · {data.user.role}</span>
			{/if}
		</div>
	</header>

	<main class="p-6">
		<h1 class="text-2xl font-bold">Board</h1>
		<p class="mt-2 text-gray-600">Job board coming soon.</p>
	</main>
</div>
