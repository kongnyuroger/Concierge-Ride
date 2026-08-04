<script lang="ts">
	import { goto } from '$app/navigation';
	import { resolve } from '$app/paths';
	import { login } from '$lib/auth';

	let email = $state('');
	let password = $state('');
	let error = $state<string | null>(null);
	let submitting = $state(false);

	async function handleSubmit(event: SubmitEvent) {
		event.preventDefault();
		error = null;
		submitting = true;

		try {
			await login(email, password);
			await goto(resolve('/dashboard'));
		} catch {
			error = 'Invalid email or password.';
		} finally {
			submitting = false;
		}
	}
</script>

<div class="mx-auto mt-16 max-w-sm">
	<h1 class="text-2xl font-bold">Sign in</h1>

	<form class="mt-6 flex flex-col gap-4" onsubmit={handleSubmit}>
		<label class="flex flex-col gap-1">
			<span class="text-sm font-medium">Email</span>
			<input
				type="email"
				bind:value={email}
				required
				autocomplete="username"
				class="rounded border px-3 py-2"
			/>
		</label>

		<label class="flex flex-col gap-1">
			<span class="text-sm font-medium">Password</span>
			<input
				type="password"
				bind:value={password}
				required
				autocomplete="current-password"
				class="rounded border px-3 py-2"
			/>
		</label>

		{#if error}
			<p class="text-sm text-red-600">{error}</p>
		{/if}

		<button
			type="submit"
			disabled={submitting}
			class="rounded bg-black px-4 py-2 text-white disabled:opacity-50"
		>
			{submitting ? 'Signing in…' : 'Sign in'}
		</button>
	</form>
</div>
