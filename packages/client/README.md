# encapsula-client

Frontend decoder client for [Encapsula](https://github.com/shudhuiami/Encapsula) API response encryption.

Automatically detects and decrypts AES-256-GCM encrypted API responses returned by the Encapsula Laravel middleware.

## Installation

```bash
npm install encapsula-client
```

## Quick Start

```ts
import { decodeEncapsulaResponse } from 'encapsula-client';

const response = await fetch('/api/users');
const body = await response.json();
const users = await decodeEncapsulaResponse(body, {
  key: import.meta.env.VITE_ENCAPSULA_KEY,
});
```

## Optional: Session Key Handshake (recommended)

If you don't want to ship a long-lived Encapsula key in your frontend bundle, use the session handshake.

Backend requirements:

- `ENCAPSULA_KEY_MODE=session`
- `ENCAPSULA_HANDSHAKE_ENABLED=true`
- `ENCAPSULA_ENABLED=true`
- The handshake endpoint must run under session middleware (default is `web`).

Frontend:

```ts
import { createEncapsulaSessionKey, decodeEncapsulaResponse } from "encapsula-client";

const key = await createEncapsulaSessionKey({ handshakeUrl: "/encapsula/handshake" });

const response = await fetch("/api/users");
const body = await response.json();

const users = await decodeEncapsulaResponse(body, { key });
```

## API

### `decodeEncapsulaResponse<T>(data, options): Promise<T>`

Decodes an Encapsula encrypted envelope. If the data is not encrypted, returns it unchanged.

```ts
const data = await decodeEncapsulaResponse(responseBody, { key: 'base64-key' });
```

### `isEncapsulaEnvelope(data): boolean`

Check whether a value is an encrypted Encapsula envelope.

```ts
if (isEncapsulaEnvelope(responseBody)) {
  // Handle encrypted response
}
```

### `attachEncapsulaAxiosInterceptor(instance, options): number`

Attach a response interceptor to an Axios instance. Returns the interceptor ID.

```ts
import axios from 'axios';
import { attachEncapsulaAxiosInterceptor } from 'encapsula-client';

const api = axios.create({ baseURL: '/api' });
attachEncapsulaAxiosInterceptor(api, {
  key: import.meta.env.VITE_ENCAPSULA_KEY,
});

const { data } = await api.get('/users'); // Decrypted automatically
```

### `createEncapsulaFetch(options): fetchFn`

Create a fetch wrapper that decrypts responses automatically.

```ts
import { createEncapsulaFetch } from 'encapsula-client';

const apiFetch = createEncapsulaFetch({
  key: import.meta.env.VITE_ENCAPSULA_KEY,
});

const users = await apiFetch('/api/users');
```

### `createEncapsulaSessionKey(options): Promise<string>`

Establish a per-session AES key with the backend using ECDH (P-256) + HKDF-SHA256.

```ts
import { createEncapsulaSessionKey } from "encapsula-client";

const key = await createEncapsulaSessionKey({
  handshakeUrl: "/encapsula/handshake",
});
```

## Vanilla JavaScript

For projects without npm, copy `examples/vanilla-js/encapsula-helper.js` into your project:

```html
<script src="encapsula-helper.js"></script>
<script>
  fetch('/api/users')
    .then(r => r.json())
    .then(data => Encapsula.decode(data, 'your-base64-key'))
    .then(users => console.log(users));
</script>
```

## Framework Examples

- [Vue](examples/vue/usage.md)
- [React](examples/react/usage.md)
- [Node.js](examples/node/usage.md)

## Environment Variables

| Framework | Variable |
|---|---|
| Vite / Vue / Nuxt | `VITE_ENCAPSULA_KEY` |
| Create React App | `REACT_APP_ENCAPSULA_KEY` |
| Next.js | `NEXT_PUBLIC_ENCAPSULA_KEY` |
| Node.js | `ENCAPSULA_KEY` |

Generate a key with the Laravel backend:

```bash
php -r "echo base64_encode(random_bytes(32));"
```

## Error Handling

```ts
try {
  const data = await decodeEncapsulaResponse(body, { key });
} catch (error) {
  // Possible errors:
  // - Invalid key length
  // - Decryption failed (wrong key or corrupted payload)
  // - Decrypted payload is not valid JSON
  console.error(error.message);
}
```

Unencrypted responses pass through unchanged — no error is thrown.

## Publishing

```bash
npm install
npm run typecheck
npm test
npm run build
npm login
npm publish
```

## Security Notes

- **HTTPS is still required.** This package does not replace TLS.
- **Not a replacement for authentication or authorization.** Use proper auth to control API access.
- **Frontend keys are visible** in built frontend apps. If you want to avoid shipping a long-lived key, use the optional session handshake mode.
- **Authenticated users can access decrypted data** in their browser. This prevents casual network tab inspection, not determined access.
- **Do not hardcode production keys** in committed source. Use environment variables.

## License

MIT
