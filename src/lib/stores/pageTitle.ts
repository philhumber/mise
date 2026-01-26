import { writable } from 'svelte/store';

interface PageTitle {
	title: string | null;
	subtitle: string | null;
	showBackButton: boolean;
}

export const pageTitle = writable<PageTitle>({ title: null, subtitle: null, showBackButton: false });

export function setPageTitle(title: string | null, subtitle: string | null = null, showBackButton = true) {
	pageTitle.set({ title, subtitle, showBackButton });
}

export function clearPageTitle() {
	pageTitle.set({ title: null, subtitle: null, showBackButton: false });
}
