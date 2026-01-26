import type { PageLoad } from './$types';

// Don't prerender - recipes are fetched from API
export const prerender = false;

export const load: PageLoad = async ({ params }) => {
	// Just pass the slug - component fetches from API
	return { slug: params.slug };
};
