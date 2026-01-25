import { getAllRecipes } from '$lib/utils/recipes';
import type { PageServerLoad } from './$types';

export const load: PageServerLoad = () => {
	return {
		recipes: getAllRecipes()
	};
};
