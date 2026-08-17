<script lang="ts">
	import { apiFetch } from '$lib/auth';
	import { labels } from '$lib/priceBook';
	import type { PriceBookEntry } from '$lib/priceBook';
	import type { PageProps } from './$types';

	let { data }: PageProps = $props();

	let entries = $state(data.entries);
	let loadError = $state(data.loadError);

	type EditableFields = {
		customer_price: number;
		included_hours: number | null;
		included_distance_km: number | null;
		overage_rate_per_hour: number | null;
		overage_rate_per_km: number | null;
		margin_floor: number;
	};

	let editingId = $state<number | null>(null);
	let form = $state<EditableFields | null>(null);
	let saving = $state(false);
	let saveError = $state<string | null>(null);

	function startEdit(entry: PriceBookEntry) {
		editingId = entry.id;
		saveError = null;
		form = {
			customer_price: entry.customer_price,
			included_hours: entry.included_hours,
			included_distance_km: entry.included_distance_km,
			overage_rate_per_hour: entry.overage_rate_per_hour,
			overage_rate_per_km: entry.overage_rate_per_km,
			margin_floor: entry.margin_floor
		};
	}

	function cancelEdit() {
		editingId = null;
		form = null;
		saveError = null;
	}

	async function refetch() {
		const response = await apiFetch('/api/price-book');

		if (!response.ok) {
			loadError = true;
			return;
		}

		const body = (await response.json()) as { data: PriceBookEntry[] };
		entries = body.data;
		loadError = false;
	}

	// Editing an entry supersedes it and creates a new row with a new id
	// (see PriceBookEntry::createNewVersion on the backend) — there's no
	// existing row to patch in place, so a full refetch after a successful
	// save is the simplest correct way to pick up the replacement.
	async function save(entry: PriceBookEntry) {
		if (!form) return;

		saving = true;
		saveError = null;

		const response = await apiFetch(`/api/price-book/${entry.id}`, {
			method: 'PUT',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify(form)
		});

		saving = false;

		if (!response.ok) {
			saveError = labels.saveError;
			return;
		}

		await refetch();
		editingId = null;
		form = null;
	}
</script>

<div class="max-w-6xl">
	<h2 class="text-2xl font-bold">{labels.pageTitle}</h2>
	<p class="mt-2 text-gray-600">{labels.pageDescription}</p>

	{#if loadError}
		<p class="mt-6 text-red-600">{labels.loadError}</p>
	{:else if entries.length === 0}
		<p class="mt-6 text-gray-600">{labels.empty}</p>
	{:else}
		<div class="mt-6 overflow-x-auto">
			<table class="w-full min-w-[960px] border-collapse text-sm">
				<thead>
					<tr class="border-b text-left text-gray-500">
						<th class="py-2 pr-4">{labels.table.city}</th>
						<th class="py-2 pr-4">{labels.table.product}</th>
						<th class="py-2 pr-4">{labels.table.tier}</th>
						<th class="py-2 pr-4">{labels.table.customerPrice}</th>
						<th class="py-2 pr-4">{labels.table.includedHours}</th>
						<th class="py-2 pr-4">{labels.table.includedDistanceKm}</th>
						<th class="py-2 pr-4">{labels.table.overageRatePerHour}</th>
						<th class="py-2 pr-4">{labels.table.overageRatePerKm}</th>
						<th class="py-2 pr-4">{labels.table.marginFloor}</th>
						<th class="py-2">{labels.table.actions}</th>
					</tr>
				</thead>
				<tbody>
					{#each entries as entry (entry.id)}
						<tr class="border-b align-top">
							<td class="py-2 pr-4">{entry.city.name}</td>
							<td class="py-2 pr-4">{entry.product.name}</td>
							<td class="py-2 pr-4">{entry.tier.name}</td>

							{#if editingId === entry.id && form}
								<td class="py-2 pr-4">
									<input
										type="number"
										min="0"
										class="w-24 rounded border px-2 py-1"
										bind:value={form.customer_price}
									/>
								</td>
								<td class="py-2 pr-4">
									<input
										type="number"
										min="0"
										class="w-20 rounded border px-2 py-1"
										bind:value={form.included_hours}
									/>
								</td>
								<td class="py-2 pr-4">
									<input
										type="number"
										min="0"
										class="w-20 rounded border px-2 py-1"
										bind:value={form.included_distance_km}
									/>
								</td>
								<td class="py-2 pr-4">
									<input
										type="number"
										min="0"
										class="w-24 rounded border px-2 py-1"
										bind:value={form.overage_rate_per_hour}
									/>
								</td>
								<td class="py-2 pr-4">
									<input
										type="number"
										min="0"
										class="w-24 rounded border px-2 py-1"
										bind:value={form.overage_rate_per_km}
									/>
								</td>
								<td class="py-2 pr-4">
									<input
										type="number"
										min="0"
										class="w-24 rounded border px-2 py-1"
										bind:value={form.margin_floor}
									/>
								</td>
								<td class="py-2">
									<div class="flex gap-2">
										<button
											type="button"
											class="rounded border px-2 py-1 hover:bg-gray-100"
											disabled={saving}
											onclick={() => save(entry)}
										>
											{labels.save}
										</button>
										<button
											type="button"
											class="rounded border px-2 py-1 hover:bg-gray-100"
											disabled={saving}
											onclick={cancelEdit}
										>
											{labels.cancel}
										</button>
									</div>
									{#if saveError}
										<p class="mt-1 text-red-600">{saveError}</p>
									{/if}
								</td>
							{:else}
								<td class="py-2 pr-4">{entry.customer_price.toLocaleString()}</td>
								<td class="py-2 pr-4">{entry.included_hours ?? labels.notSet}</td>
								<td class="py-2 pr-4">{entry.included_distance_km ?? labels.notSet}</td>
								<td class="py-2 pr-4">
									{entry.overage_rate_per_hour?.toLocaleString() ?? labels.notSet}
								</td>
								<td class="py-2 pr-4">
									{entry.overage_rate_per_km?.toLocaleString() ?? labels.notSet}
								</td>
								<td class="py-2 pr-4">{entry.margin_floor.toLocaleString()}</td>
								<td class="py-2">
									<button
										type="button"
										class="rounded border px-2 py-1 hover:bg-gray-100"
										onclick={() => startEdit(entry)}
									>
										{labels.edit}
									</button>
								</td>
							{/if}
						</tr>
					{/each}
				</tbody>
			</table>
		</div>
	{/if}
</div>
