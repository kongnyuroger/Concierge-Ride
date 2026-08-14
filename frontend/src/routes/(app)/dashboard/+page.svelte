<script lang="ts">
	import { logout } from '$lib/auth';

	const navigation = [
		'Inbox',
		'Board',
		'Schedule',
		'Fleet',
		'Payments',
		'Customers',
		'Companies / Analytics'
	];
</script>

<div class="min-h-screen bg-gray-50">
	<header class="flex items-center justify-between border-b bg-white px-6 py-4">
		<h1 class="text-xl font-bold">Concierge Ride</h1>

		<button
			type="button"
			onclick={() => logout()}
			class="rounded border px-4 py-2 text-sm hover:bg-gray-100"
		>
			Sign out
		</button>
	</header>

	<div class="flex min-h-[calc(100vh-73px)]">
		<aside class="w-64 border-r bg-white p-4">
			<nav aria-label="Main navigation">
				<h2 class="mb-4 text-sm font-semibold text-gray-500">Navigation</h2>

				<ul class="space-y-1">
					{#each navigation as item (item)}
						<li>
							<button
								type="button"
								class="block w-full rounded px-3 py-2 text-left text-sm hover:bg-gray-100"
							>
								{item}
							</button>
						</li>
					{/each}
				</ul>
			</nav>
		</aside>

		<main class="flex-1 p-8">
			<h2 class="text-2xl font-bold">Dashboard</h2>

			<p class="mt-2 text-gray-600">Select a section from the navigation to get started.</p>
		</main>
	</div>
</div>
