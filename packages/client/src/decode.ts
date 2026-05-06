import type { EncapsulaEnvelope } from "./types";

/**
 * Check whether a value is an Encapsula encrypted envelope.
 */
export function isEncapsulaEnvelope(data: unknown): data is EncapsulaEnvelope {
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
 * Import a base64-encoded 256-bit key for AES-GCM decryption.
 */
export async function importKey(base64Key: string): Promise<CryptoKey> {
  const raw = Uint8Array.from(atob(base64Key), (c) => c.charCodeAt(0));

  if (raw.length !== 32) {
    throw new Error(
      `[Encapsula] Invalid key length: expected 32 bytes, got ${raw.length}.`
    );
  }

  return crypto.subtle.importKey("raw", raw, { name: "AES-GCM" }, false, [
    "decrypt",
  ]);
}

/**
 * Decode an Encapsula encrypted response envelope.
 *
 * @param data - The response body (may or may not be an encrypted envelope).
 * @param options - Object containing the base64-encoded decryption key.
 * @returns The original JSON data if encrypted, or the input data unchanged.
 */
export async function decodeEncapsulaResponse<T = unknown>(
  data: unknown,
  options: { key: string }
): Promise<T> {
  if (!isEncapsulaEnvelope(data)) {
    return data as T;
  }

  const key = await importKey(options.key);

  const ciphertext = Uint8Array.from(atob(data.payload), (c) =>
    c.charCodeAt(0)
  );
  const iv = Uint8Array.from(atob(data.iv), (c) => c.charCodeAt(0));
  const tag = Uint8Array.from(atob(data.tag), (c) => c.charCodeAt(0));

  // AES-GCM expects ciphertext + tag concatenated
  const combined = new Uint8Array(ciphertext.length + tag.length);
  combined.set(ciphertext);
  combined.set(tag, ciphertext.length);

  let decrypted: ArrayBuffer;
  try {
    decrypted = await crypto.subtle.decrypt(
      { name: "AES-GCM", iv, tagLength: 128 },
      key,
      combined
    );
  } catch {
    throw new Error(
      "[Encapsula] Decryption failed. The key may be incorrect or the payload may be corrupted."
    );
  }

  const text = new TextDecoder().decode(decrypted);

  try {
    return JSON.parse(text) as T;
  } catch {
    throw new Error("[Encapsula] Decrypted payload is not valid JSON.");
  }
}
