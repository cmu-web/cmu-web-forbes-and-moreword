# Forbes & Moreword: Notes for AI Agents

Read [CONTRIBUTING.md](CONTRIBUTING.md) and [DEVELOPMENT.md](DEVELOPMENT.md)
before changing anything. The rules agents most often break here:

- Pages use CMU's template and the Web Kilt, nothing else.
- Before writing markup, find the component: ask the Web Kilt MCP server
  (https://webkilt.andrew.cmu.edu/mcp) or browse the Components page
  (https://webkilt.andrew.cmu.edu/0/components.html).
- Use the template's classes first, then the Kilt's (cmu-). The theme adds
  no CSS of its own: never add a theme class or a stylesheet rule. If the
  Kilt lacks something, say so and stop; it gets added to the Kilt.
- Never copy the template's CSS or values; pages load it from www.cmu.edu.
- `docs/` is the public docs site's source, built into WordPress by
  `docs/seed.sh`; don't put notes there.
- The hosted docs site is managed only through its image and WP-CLI. Nobody
  signs in, and `docs/mu-plugins/` keeps it that way.
- Made-up people only (e.g., Andrew Carnegie, ac00).
- Follow the Web Kilt's [writing conventions](https://webkilt.andrew.cmu.edu/0/conventions.html).
- Versions use semantic versioning as plain numbers (e.g., `0.1.0`).
