import adapter from '@sveltejs/adapter-static';

/** @type {import('@sveltejs/kit').Config} */
const config = {
	kit: {
		adapter: adapter({
			fallback: '404.html',
			strict: false
		}),
		paths: {
			base: '/mise'
		},
		alias: {
			$components: 'src/lib/components',
			$styles: 'src/lib/styles',
			$assets: 'src/lib/assets'
		},
		prerender: {
			handleHttpError: 'warn'
		}
	}
};

export default config;
