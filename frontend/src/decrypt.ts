/**
 * Encapsula frontend decryption helper.
 *
 * Uses the Web Crypto API to decrypt AES-256-GCM encrypted payloads
 * returned by the Encapsula Laravel middleware.
 */

export interface EncryptedEnvelope {
  encrypted: boolean;
  payload: string;
  iv: string;
  tag: string;
  alg: string;
}

/**
 * Check whether a response body is an Encapsula encrypted envelope.
 */
export function isEncryptedEnvelope(data: unknown): data is EncryptedEnvelope {
  if (typeof data !== "object" || data === null) return false;
  const obj = data as Record<string, unknown>;
  return (
    obj.encrypted === true &&
    typeof obj.payload === "string" &&
    typeof obj.iv === "string" &&
    typeof obj.tag === "string" &&
    typeof obj.alg === "string"
  );
}

/**
 * Import a base64-encoded 256-bit key for use with Web Crypto API.
 */
export async function importKey(base64Key: string): Promise<CryptoKey> {
  const keyBytes = Uint8Array.from(atob(base64Key), (c) => c.charCodeAt(0));
  return crypto.subtle.importKey("raw", keyBytes, { name: "AES-GCM" }, false, [
    "decrypt",
  ]);
}

/**
 * Decrypt an Encapsula encrypted envelope.
 *
 * @param envelope - The encrypted response envelope.
 * @param key - A CryptoKey imported via importKey().
 * @returns The decrypted JSON data.
 */
export async function decrypt<T = unknown>(
  envelope: EncryptedEnvelope,
  key: CryptoKey
): Promise<T> {
  const ciphertext = Uint8Array.from(atob(envelope.payload), (c) =>
    c.charCodeAt(0)
  );
  const iv = Uint8Array.from(atob(envelope.iv), (c) => c.charCodeAt(0));
  const tag = Uint8Array.from(atob(envelope.tag), (c) => c.charCodeAt(0));

  // AES-GCM expects ciphertext + tag concatenated
  const combined = new Uint8Array(ciphertext.length + tag.length);
  combined.set(ciphertext);
  combined.set(tag, ciphertext.length);

  const decrypted = await crypto.subtle.decrypt(
    { name: "AES-GCM", iv, tagLength: 128 },
    key,
    combined
  );

  const text = new TextDecoder().decode(decrypted);
  return JSON.parse(text) as T;
}
