<script lang="ts">
	import { logout } from '$lib/auth';
	import { NAV_ITEMS } from '$lib/nav';
</script>

<div class="min-h-screen bg-gray-50">
	<header
		class="flex flex-wrap items-center justify-between gap-3 border-b bg-white px-4 py-3 md:px-6 md:py-4"
	>
		<h1 class="min-w-0 truncate text-lg font-bold md:text-xl">Concierge Ride</h1>

		<button
			type="button"
			onclick={() => logout()}
			class="shrink-0 rounded border px-4 py-2 text-sm hover:bg-gray-100"
		>
			Sign out
		</button>
	</header>

	<div class="flex min-w-0 flex-col md:min-h-[calc(100vh-73px)] md:flex-row">
		<aside class="min-w-0 border-b bg-white p-4 md:w-64 md:shrink-0 md:border-r md:border-b-0">
			<!-- eslint-disable svelte/no-navigation-without-resolve -- these
			     pages don't exist yet (resolve() only accepts real routes);
			     the tickets building each section add the real page. -->
			<nav aria-label="Main navigation">
				<h2 class="mb-4 text-sm font-semibold text-gray-500">Navigation</h2>

				<ul class="flex min-w-0 gap-1 overflow-x-auto md:flex-col md:overflow-visible">
					{#each NAV_ITEMS as item (item.href)}
						<li>
							<a
								href={item.href}
								class="block rounded px-3 py-2 text-left text-sm whitespace-nowrap hover:bg-gray-100 md:w-full"
							>
								{item.label}
							</a>
						</li>
					{/each}
				</ul>
			</nav>
			<!-- eslint-enable svelte/no-navigation-without-resolve -->
		</aside>

		<main class="min-w-0 flex-1 p-8">
			<h2 class="text-2xl font-bold">Dashboard</h2>

			<p class="mt-2 text-gray-600">Select a section from the navigation to get started.</p>
		</main>
	</div>
</div>
