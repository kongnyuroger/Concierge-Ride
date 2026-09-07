/**
 * BR-16's permission matrix, frontend side — see
 * /docs/adr/0011-roles-and-permissions.md.
 *
 * IMPORTANT: this is UX only. Hiding a nav item here just avoids showing a
 * dispatcher a link that would 403 — it enforces nothing. The real
 * enforcement is server-side (route `permission:` middleware + the owner
 * Gate::before bypass, backend/routes/api.php and AppServiceProvider). A
 * disallowed request against the API directly is refused regardless of
 * whether this file exists at all.
 *
 * Permission name strings must match backend/app/Enums/Permission.php —
 * there's no shared source between PHP and TypeScript here, so if that enum
 * changes, update this list too.
 */

export function can(permissions: string[], permission: string): boolean {
	return permissions.includes(permission);
}

export type NavItem = {
	label: string;
	href: string;
	/** null = no permission required (visible to any authenticated user). */
	permission: string | null;
};

// Hrefs below point at pages that don't exist yet — CR-11 (leads/jobs/
// customers/individual queue), CR-13 (price book/driver rates), CR-20
// (accounts/company queue), CR-32 (audit log) build them. The route
// structure and gating are established now so those tickets just add a
// +page.svelte at each path.
export const NAV_ITEMS: NavItem[] = [
	{ label: 'Dashboard', href: '/dashboard', permission: null },
	{ label: 'Board', href: '/board', permission: 'jobs.manage' },
	{ label: 'Leads', href: '/leads', permission: 'leads.manage' },
	{ label: 'Jobs', href: '/jobs', permission: 'jobs.manage' },
	{ label: 'Customers', href: '/customers', permission: 'customers.manage' },
	{ label: 'Individual queue', href: '/queues/individual', permission: 'queue.individual.view' },
	{ label: 'Company queue', href: '/queues/company', permission: 'queue.company.view' },
	{ label: 'Price book', href: '/price-book', permission: 'price-book.manage' },
	{ label: 'Driver rates', href: '/driver-rates', permission: 'driver-rates.manage' },
	{ label: 'Accounts', href: '/accounts', permission: 'accounts.manage' },
	{ label: 'Audit log', href: '/audit-log', permission: 'audit-log.view' }
];

export function visibleNavItems(permissions: string[]): NavItem[] {
	return NAV_ITEMS.filter((item) => item.permission === null || can(permissions, item.permission));
}
