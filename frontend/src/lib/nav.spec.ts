import { describe, it, expect } from 'vitest';
import { NAV_ITEMS } from './nav';

describe('NAV_ITEMS', () => {
	it('has exactly the seven CR-8 sections, in order', () => {
		expect(NAV_ITEMS.map((item) => item.label)).toEqual([
			'Inbox',
			'Board',
			'Schedule',
			'Fleet',
			'Payments',
			'Customers',
			'Companies/Analytics'
		]);
	});

	it('gives every item a distinct href', () => {
		const hrefs = NAV_ITEMS.map((item) => item.href);
		expect(new Set(hrefs).size).toBe(hrefs.length);
	});
});
