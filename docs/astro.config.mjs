// @ts-check
import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';

// https://astro.build/config
export default defineConfig({
	site: 'https://stumason.github.io',
	base: '/laravel-kick',
	integrations: [
		starlight({
			title: 'Laravel Kick',
			description: 'MCP server and REST API for Laravel application introspection',
			social: [
				{ icon: 'github', label: 'GitHub', href: 'https://github.com/StuMason/laravel-kick' },
			],
			editLink: {
				baseUrl: 'https://github.com/StuMason/laravel-kick/edit/main/docs/',
			},
			customCss: ['./src/styles/custom.css'],
			sidebar: [
				{
					label: 'Getting Started',
					items: [
						{ label: 'Introduction', slug: 'getting-started/introduction' },
						{ label: 'Installation', slug: 'getting-started/installation' },
						{ label: 'Configuration', slug: 'getting-started/configuration' },
					],
				},
				{
					label: 'MCP Integration',
					items: [
						{ label: 'Overview', slug: 'mcp/overview' },
						{ label: 'MCP Client Setup', slug: 'mcp/claude-desktop' },
						{ label: 'Available Tools', slug: 'mcp/tools' },
					],
				},
				{
					label: 'REST API',
					items: [
						{ label: 'Authentication', slug: 'api/authentication' },
						{ label: 'Endpoints', slug: 'api/endpoints' },
					],
				},
				{
					label: 'Reference',
					items: [
						{ label: 'Configuration', slug: 'reference/configuration' },
						{ label: 'Security', slug: 'reference/security' },
					],
				},
			],
		}),
	],
});
