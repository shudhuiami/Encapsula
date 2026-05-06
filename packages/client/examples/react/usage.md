# Encapsula — React Usage

## Setup

```bash
npm install @encapsula/client
```

## Environment Variable

Add to your `.env`:

```
VITE_ENCAPSULA_KEY=your-base64-encoded-32-byte-key
```

For Create React App, use `REACT_APP_ENCAPSULA_KEY` instead.

## Hook Example

```ts
// hooks/useEncapsulaFetch.ts
import { useState, useEffect } from 'react';
import { createEncapsulaFetch } from '@encapsula/client';

const apiFetch = createEncapsulaFetch({
  key: import.meta.env.VITE_ENCAPSULA_KEY,
});

export function useEncapsulaFetch<T>(url: string) {
  const [data, setData] = useState<T | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<Error | null>(null);

  useEffect(() => {
    let cancelled = false;

    apiFetch<T>(url)
      .then((result) => {
        if (!cancelled) setData(result);
      })
      .catch((err) => {
        if (!cancelled) setError(err);
      })
      .finally(() => {
        if (!cancelled) setLoading(false);
      });

    return () => { cancelled = true; };
  }, [url]);

  return { data, loading, error };
}
```

## Component Usage

```tsx
import { useEncapsulaFetch } from './hooks/useEncapsulaFetch';

interface User {
  id: number;
  name: string;
}

function UserList() {
  const { data: users, loading, error } = useEncapsulaFetch<User[]>('/api/users');

  if (loading) return <p>Loading...</p>;
  if (error) return <p>Error: {error.message}</p>;

  return (
    <ul>
      {users?.map((user) => (
        <li key={user.id}>{user.name}</li>
      ))}
    </ul>
  );
}
```

## With Axios

```ts
import axios from 'axios';
import { attachEncapsulaAxiosInterceptor } from '@encapsula/client';

const api = axios.create({ baseURL: '/api' });

attachEncapsulaAxiosInterceptor(api, {
  key: import.meta.env.VITE_ENCAPSULA_KEY,
});

// Use api.get(), api.post(), etc. — responses are decrypted automatically.
```
