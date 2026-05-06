import type { AxiosInstance, AxiosResponse } from "axios";
import { decodeEncapsulaResponse } from "./decode";

/**
 * Attach an Encapsula decryption interceptor to an Axios instance.
 *
 * Automatically detects and decrypts encrypted API responses
 * before they reach your application code.
 *
 * @param instance - The Axios instance to attach the interceptor to.
 * @param options - Object containing the base64-encoded decryption key.
 * @returns The interceptor ID (can be used to eject later).
 */
export function attachEncapsulaAxiosInterceptor(
  instance: AxiosInstance,
  options: { key: string }
): number {
  return instance.interceptors.response.use(
    async (response: AxiosResponse): Promise<AxiosResponse> => {
      try {
        response.data = await decodeEncapsulaResponse(response.data, options);
      } catch (error) {
        console.error("[Encapsula] Axios interceptor decryption failed:", error);
      }
      return response;
    }
  );
}
