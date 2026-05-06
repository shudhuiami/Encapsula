function arrayBufferToBase64(buf: ArrayBuffer): string {
  const bytes = new Uint8Array(buf);
  let binary = "";
  for (let i = 0; i < bytes.length; i++) binary += String.fromCharCode(bytes[i]);
  return btoa(binary);
}

function base64ToArrayBuffer(b64: string): ArrayBuffer {
  const binary = atob(b64);
  const bytes = new Uint8Array(binary.length);
  for (let i = 0; i < binary.length; i++) bytes[i] = binary.charCodeAt(i);
  return bytes.buffer;
}

/**
 * Establish a per-session AES-256-GCM key with the backend using ECDH (P-256) + HKDF-SHA256.
 *
 * The backend stores the derived key in the server session; the client returns the same key
 * so you can pass it to decode/interceptors.
 */
export async function createEncapsulaSessionKey(options: {
  handshakeUrl: string;
  fetch?: typeof fetch;
}): Promise<string> {
  const fetchImpl = options.fetch ?? fetch;

  const keyPair = await crypto.subtle.generateKey(
    { name: "ECDH", namedCurve: "P-256" },
    true,
    ["deriveBits"]
  );

  const clientPublicSpki = await crypto.subtle.exportKey("spki", keyPair.publicKey);
  const clientPublicKeyB64 = arrayBufferToBase64(clientPublicSpki);

  const res = await fetchImpl(options.handshakeUrl, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ client_public_key: clientPublicKeyB64 }),
    credentials: "include",
  });

  if (!res.ok) {
    throw new Error(`[Encapsula] Handshake failed with status ${res.status}.`);
  }

  const json = (await res.json()) as {
    server_public_key: string;
    salt: string;
  };

  const serverPublicSpki = base64ToArrayBuffer(json.server_public_key);
  const serverPublicKey = await crypto.subtle.importKey(
    "spki",
    serverPublicSpki,
    { name: "ECDH", namedCurve: "P-256" },
    false,
    []
  );

  const sharedBits = await crypto.subtle.deriveBits(
    { name: "ECDH", public: serverPublicKey },
    keyPair.privateKey,
    256
  );

  const ikmKey = await crypto.subtle.importKey("raw", sharedBits, "HKDF", false, [
    "deriveBits",
  ]);

  const salt = new Uint8Array(base64ToArrayBuffer(json.salt));
  const info = new TextEncoder().encode("encapsula-session-key-v1");

  const keyBits = await crypto.subtle.deriveBits(
    { name: "HKDF", hash: "SHA-256", salt, info },
    ikmKey,
    256
  );

  return arrayBufferToBase64(keyBits);
}

