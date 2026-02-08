import { writable } from 'svelte/store';

interface PageTitle {
	title: string | null;
	subtitle: string | null;
	showBackButton: boolean;
	backUrl: string | null;
}

export const pageTitle = writable<PageTitle>({
	title: null,
	subtitle: null,
	showBackButton: false,
	backUrl: null
});

export function setPageTitle(
	title: string | null,
	subtitle: string | null = null,
	showBackButton = true,
	backUrl: string | null = null
) {
	pageTitle.set({ title, subtitle, showBackButton, backUrl });
}

export function clearPageTitle() {
	pageTitle.set({ title: null, subtitle: null, showBackButton: false, backUrl: null });
}
