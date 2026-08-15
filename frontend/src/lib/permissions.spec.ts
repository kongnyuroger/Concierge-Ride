import { describe, it, expect } from 'vitest';
import { can, visibleNavItems } from './permissions';

describe('can', () => {
	it('is true when the permission is present', () => {
		expect(can(['leads.manage'], 'leads.manage')).toBe(true);
	});

	it('is false when the permission is absent', () => {
		expect(can(['leads.manage'], 'audit-log.view')).toBe(false);
	});
});

describe('visibleNavItems', () => {
	it('always includes items with no permission requirement', () => {
		const items = visibleNavItems([]);

		expect(items.map((item) => item.label)).toContain('Dashboard');
	});

	it('hides items the given permissions do not cover', () => {
		const items = visibleNavItems(['leads.manage']);

		expect(items.map((item) => item.label)).toContain('Leads');
		expect(items.map((item) => item.label)).not.toContain('Price book');
		expect(items.map((item) => item.label)).not.toContain('Audit log');
	});

	it('shows every gated item when given the full permission set', () => {
		const allPermissions = [
			'leads.manage',
			'jobs.manage',
			'customers.manage',
			'queue.individual.view',
			'queue.company.view',
			'price-book.manage',
			'driver-rates.manage',
			'accounts.manage',
			'audit-log.view'
		];

		const items = visibleNavItems(allPermissions);

		expect(items).toHaveLength(10); // 9 gated + Dashboard
	});
});
