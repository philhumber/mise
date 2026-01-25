import { error } from '@sveltejs/kit';
import { getRecipeBySlug, RecipeNotFoundError } from '$lib/utils/recipes';
import type { PageServerLoad } from './$types';

export const load: PageServerLoad = ({ params }) => {
	try {
		const recipe = getRecipeBySlug(params.slug);
		return { recipe };
	} catch (err) {
		if (err instanceof RecipeNotFoundError) {
			error(404, {
				message: `Recipe "${params.slug}" not found`
			});
		}
		throw err;
	}
};
