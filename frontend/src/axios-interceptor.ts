/**
 * Encapsula Axios response interceptor.
 *
 * Automatically detects and decrypts encrypted API responses
 * before they reach your application code.
 *
 * Usage:
 *   import axios from 'axios';
 *   import { createEncapsulaInterceptor } from './axios-interceptor';
 *
 *   const interceptor = await createEncapsulaInterceptor('your-base64-key');
 *   axios.interceptors.response.use(interceptor);
 */

import type { AxiosResponse } from "axios";
import { decrypt, importKey, isEncryptedEnvelope } from "./decrypt";

/**
 * Create an Axios response interceptor that decrypts Encapsula envelopes.
 *
 * @param base64Key - The base64-encoded 256-bit decryption key.
 * @returns An Axios response interceptor function.
 */
export async function createEncapsulaInterceptor(
  base64Key: string
): Promise<(response: AxiosResponse) => Promise<AxiosResponse>> {
  const key = await importKey(base64Key);

  return async (response: AxiosResponse): Promise<AxiosResponse> => {
    if (!isEncryptedEnvelope(response.data)) {
      return response;
    }

    try {
      response.data = await decrypt(response.data, key);
    } catch (error) {
      console.error("[Encapsula] Decryption failed:", error);
    }

    return response;
  };
}
