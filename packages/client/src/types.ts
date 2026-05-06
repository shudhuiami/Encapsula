/**
 * Encapsula encrypted response envelope as returned by the Laravel middleware.
 */
export interface EncapsulaEnvelope {
  encrypted: true;
  payload: string;
  iv: string;
  tag: string;
  alg: string;
}

/**
 * Configuration options for Encapsula decoding.
 */
export interface EncapsulaOptions {
  /** Base64-encoded 256-bit decryption key. */
  key: string;
}
