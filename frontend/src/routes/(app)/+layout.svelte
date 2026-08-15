<script lang="ts">
	import { logout } from '$lib/auth';
	import { visibleNavItems } from '$lib/permissions';
	import type { LayoutProps } from './$types';

	let { data, children }: LayoutProps = $props();
	let permissions = $derived(data.user?.permissions ?? []);
	let navItems = $derived(visibleNavItems(permissions));
</script>

<div class="min-h-screen">
	<header class="flex items-center justify-between border-b px-6 py-3">
		<!-- Most of these hrefs don't have a page yet (CR-11/13/20/32 build
		     them) — plain hrefs, not resolve(), since resolve() only accepts
		     routes that already exist. -->
		<!-- eslint-disable svelte/no-navigation-without-resolve -->
		<nav class="flex gap-4 text-sm">
			{#each navItems as item (item.href)}
				<a href={item.href} class="text-gray-700 hover:underline">{item.label}</a>
			{/each}
		</nav>
		<!-- eslint-enable svelte/no-navigation-without-resolve -->

		<div class="flex items-center gap-3 text-sm">
			{#if data.user}
				<span class="text-gray-500">{data.user.name} · {data.user.role}</span>
			{/if}
			<button type="button" onclick={() => logout()} class="rounded border px-3 py-1">
				Sign out
			</button>
		</div>
	</header>

	<main class="p-6">
		{@render children()}
	</main>
</div>
