# Encapsula Standalone Frontend Helpers

This directory is intentionally kept for projects that do not want to install the npm client package.

Use these files when you need simple copy-paste or manual frontend integration.

## When to use this directory

Use `frontend/` when:

- The project is plain HTML, CSS, and JavaScript.
- The project does not use npm.
- You want to copy a small helper into an existing frontend.
- You want a quick reference implementation.

## When to use the npm package

Use `packages/client/` when:

- The project uses npm, pnpm, or yarn.
- The project is built with Vue, React, Next.js, Nuxt, Vite, or Node tooling.
- You want TypeScript support and package-managed updates.

## Files

```txt
frontend/src/decrypt.ts
frontend/src/axios-interceptor.ts
frontend/src/fetch-client.ts
```

## Important repository rule

Do not delete this directory when changing `packages/client/`.

The two frontend integration paths are intentional:

1. `frontend/` = copy-paste/manual helpers.
2. `packages/client/` = installable npm package.
