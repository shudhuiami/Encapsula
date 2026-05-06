# Encapsula — Vue Usage

## Setup

```bash
npm install encapsula-client axios
```

## Environment Variable

Add to your `.env`:

```
VITE_ENCAPSULA_KEY=your-base64-encoded-32-byte-key
```

## Composable Example

```ts
// composables/useApi.ts
import axios from 'axios';
import { attachEncapsulaAxiosInterceptor } from 'encapsula-client';

const api = axios.create({
  baseURL: '/api',
  headers: { 'Accept': 'application/json' },
});

attachEncapsulaAxiosInterceptor(api, {
  key: import.meta.env.VITE_ENCAPSULA_KEY,
});

export function useApi() {
  return { api };
}
```

## Component Usage

```vue
<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useApi } from '@/composables/useApi';

const { api } = useApi();
const users = ref([]);

onMounted(async () => {
  const { data } = await api.get('/users');
  users.value = data; // Already decrypted
});
</script>

<template>
  <ul>
    <li v-for="user in users" :key="user.id">{{ user.name }}</li>
  </ul>
</template>
```

## Without Axios

```ts
import { createEncapsulaFetch } from 'encapsula-client';

const apiFetch = createEncapsulaFetch({
  key: import.meta.env.VITE_ENCAPSULA_KEY,
});

const users = await apiFetch('/api/users');
```
