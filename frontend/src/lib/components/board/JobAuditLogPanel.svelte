
<script lang="ts">
  import { onMount } from 'svelte';
  import { userStore } from '$lib/stores/userStore'; // Contains auth state ($userStore.role)

  export let jobId: string;

  interface AuditLog {
    id: string;
    action: string;
    old_values: Record<string, any>;
    new_values: Record<string, any>;
    created_at: string;
    user: { name: string; role: string } | null;
  }

  let logs: AuditLog[] = [];
  let loading = true;
  let error = '';

  onMount(async () => {
    if ($userStore?.role !== 'owner') {
      error = 'Unauthorized access.';
      loading = false;
      return;
    }

    try {
      const res = await fetch(`/api/jobs/${jobId}/audit-logs`);
      if (!res.ok) throw new Error('Failed to load audit history');
      
      const json = await res.json();
      logs = json.data;
    } catch (err: any) {
      error = err.message;
    } finally {
      loading = false;
    }
  });
</script>

{#if $userStore?.role === 'owner'}
  <div class="mt-6 border-t pt-4">
    <h3 class="text-lg font-bold text-gray-900 mb-3">Audit Log & Change History</h3>

    {#if loading}
      <p class="text-sm text-gray-500">Loading audit history...</p>
    {:else if error}
      <p class="text-sm text-red-500">{error}</p>
    {:else if logs.length === 0}
      <p class="text-sm text-gray-500">No change history recorded.</p>
    {:else}
      <div class="space-y-3">
        {#each logs as log}
          <div class="p-3 bg-gray-50 rounded border text-sm">
            <div class="flex justify-between font-semibold text-gray-700">
              <span>{log.user ? log.user.name : 'System'} ({log.action})</span>
              <span class="text-xs text-gray-500">{new Date(log.created_at).toLocaleString()}</span>
            </div>

            {#if log.new_values}
              <ul class="mt-2 text-xs space-y-1 font-mono">
                {#each Object.entries(log.new_values) as [field, newVal]}
                  <li class="text-gray-800">
                    <span class="font-bold">{field}:</span> 
                    <span class="line-through text-red-600">{log.old_values?.[field] ?? 'null'}</span> 
                    → 
                    <span class="text-green-600">{newVal}</span>
                  </li>
                {/each}
              </ul>
            {/if}
          </div>
        {/each}
      </div>
    {/if}
  </div>
{/if}