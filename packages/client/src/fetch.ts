import { decodeEncapsulaResponse } from "./decode";

/**
 * Create a fetch wrapper that automatically decrypts Encapsula responses.
 *
 * @param options - Object containing the base64-encoded decryption key.
 * @returns A function similar to fetch that returns decrypted JSON data.
 */
export function createEncapsulaFetch(
  options: { key: string }
): <T = unknown>(input: RequestInfo | URL, init?: RequestInit) => Promise<T> {
  return async <T = unknown>(
    input: RequestInfo | URL,
    init?: RequestInit
  ): Promise<T> => {
    const response = await fetch(input, init);

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }

    const data: unknown = await response.json();
    return decodeEncapsulaResponse<T>(data, options);
  };
}
