# Encapsula — Node.js Usage

## Setup

```bash
npm install encapsula-client
```

## Environment Variable

```bash
export ENCAPSULA_KEY=your-base64-encoded-32-byte-key
```

## Basic Usage

```ts
import { decodeEncapsulaResponse } from 'encapsula-client';

const response = await fetch('https://your-api.com/api/users');
const body = await response.json();

const users = await decodeEncapsulaResponse(body, {
  key: process.env.ENCAPSULA_KEY!,
});

console.log(users);
```

## With Axios

```ts
import axios from 'axios';
import { attachEncapsulaAxiosInterceptor } from 'encapsula-client';

const api = axios.create({
  baseURL: 'https://your-api.com/api',
});

attachEncapsulaAxiosInterceptor(api, {
  key: process.env.ENCAPSULA_KEY!,
});

const { data: users } = await api.get('/users');
console.log(users);
```

## With Fetch Wrapper

```ts
import { createEncapsulaFetch } from 'encapsula-client';

const apiFetch = createEncapsulaFetch({
  key: process.env.ENCAPSULA_KEY!,
});

const users = await apiFetch('https://your-api.com/api/users');
console.log(users);
```

## Note on Node.js Crypto

Node.js 18+ includes the Web Crypto API globally (`crypto.subtle`).
For Node.js 16, you may need to use `globalThis.crypto` from the `crypto` module:

```ts
import { webcrypto } from 'crypto';
globalThis.crypto = webcrypto as any;
```
