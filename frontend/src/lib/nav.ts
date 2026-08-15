/**
 * CR-8's seven dashboard nav sections. Kept as a plain data array (not
 * inline in the page) so CR-15's role-based nav gating can filter this
 * list later without touching the rendering — that ticket adds a
 * `permission` field per item and a filter step, not a rewrite of this
 * file or the layout that renders it.
 *
 * Hrefs point at pages that don't exist yet — the tickets that build each
 * section (Inbox/Board/Schedule/Fleet/Payments/Customers/Companies) add
 * the actual +page.svelte at each path; this ticket is the shell only.
 */
export type NavItem = {
	label: string;
	href: string;
};

export const NAV_ITEMS: NavItem[] = [
	{ label: 'Inbox', href: '/inbox' },
	{ label: 'Board', href: '/board' },
	{ label: 'Schedule', href: '/schedule' },
	{ label: 'Fleet', href: '/fleet' },
	{ label: 'Payments', href: '/payments' },
	{ label: 'Customers', href: '/customers' },
	{ label: 'Companies/Analytics', href: '/companies' }
];
