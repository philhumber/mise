import adapter from '@sveltejs/adapter-static';

/** @type {import('@sveltejs/kit').Config} */
const config = {
	kit: {
		adapter: adapter({
			fallback: '404.html',
			strict: true
		}),
		paths: {
			base: '/mise'
		}
	}
};

export default config;
