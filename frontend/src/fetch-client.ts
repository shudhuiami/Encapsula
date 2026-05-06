/**
 * Encapsula Fetch wrapper with automatic decryption.
 *
 * Wraps the native fetch API and automatically decrypts
 * Encapsula encrypted responses.
 *
 * Usage:
 *   import { createEncapsulaFetch } from './fetch-client';
 *
 *   const efetch = await createEncapsulaFetch('your-base64-key');
 *   const data = await efetch('/api/users');
 */

import { decrypt, importKey, isEncryptedEnvelope } from "./decrypt";

/**
 * Create a fetch wrapper that decrypts Encapsula encrypted responses.
 *
 * @param base64Key - The base64-encoded 256-bit decryption key.
 * @returns A function with the same signature as fetch, but returns decrypted JSON.
 */
export async function createEncapsulaFetch(
  base64Key: string
): Promise<
  <T = unknown>(input: RequestInfo | URL, init?: RequestInit) => Promise<T>
> {
  const key = await importKey(base64Key);

  return async <T = unknown>(
    input: RequestInfo | URL,
    init?: RequestInit
  ): Promise<T> => {
    const response = await fetch(input, init);

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const data: unknown = await response.json();

    if (isEncryptedEnvelope(data)) {
      return decrypt<T>(data, key);
    }

    return data as T;
  };
}
