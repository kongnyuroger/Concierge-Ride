<script lang="ts">
	import { logout } from '$lib/auth';
</script>

<div class="mx-auto mt-16 max-w-lg">
	<h1 class="text-2xl font-bold">Dashboard</h1>
	<p class="mt-2 text-gray-600">Signed in. Nothing here yet.</p>

	<button type="button" onclick={() => logout()} class="mt-6 rounded border px-4 py-2">
		Sign out
	</button>
</div>
