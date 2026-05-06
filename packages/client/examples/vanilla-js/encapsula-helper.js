/**
 * Encapsula Vanilla JS Helper
 *
 * Copy this file into your project. No npm dependencies required.
 * Uses the browser Web Crypto API for AES-256-GCM decryption.
 *
 * Usage:
 *   <script src="encapsula-helper.js"></script>
 *   <script>
 *     const key = 'your-base64-encoded-32-byte-key';
 *     fetch('/api/users')
 *       .then(r => r.json())
 *       .then(data => Encapsula.decode(data, key))
 *       .then(users => console.log(users));
 *   </script>
 */
(function (global) {
  "use strict";

  function isEncrypted(data) {
    return (
      data !== null &&
      typeof data === "object" &&
      data.encrypted === true &&
      typeof data.payload === "string" &&
      typeof data.iv === "string" &&
      typeof data.tag === "string"
    );
  }

  function b64ToBytes(base64) {
    var binary = atob(base64);
    var bytes = new Uint8Array(binary.length);
    for (var i = 0; i < binary.length; i++) {
      bytes[i] = binary.charCodeAt(i);
    }
    return bytes;
  }

  async function importKey(base64Key) {
    var keyBytes = b64ToBytes(base64Key);
    if (keyBytes.length !== 32) {
      throw new Error(
        "[Encapsula] Invalid key length: expected 32 bytes, got " +
          keyBytes.length
      );
    }
    return crypto.subtle.importKey("raw", keyBytes, { name: "AES-GCM" }, false, [
      "decrypt",
    ]);
  }

  async function decode(data, base64Key) {
    if (!isEncrypted(data)) {
      return data;
    }

    var key = await importKey(base64Key);
    var ciphertext = b64ToBytes(data.payload);
    var iv = b64ToBytes(data.iv);
    var tag = b64ToBytes(data.tag);

    var combined = new Uint8Array(ciphertext.length + tag.length);
    combined.set(ciphertext);
    combined.set(tag, ciphertext.length);

    var decrypted = await crypto.subtle.decrypt(
      { name: "AES-GCM", iv: iv, tagLength: 128 },
      key,
      combined
    );

    var text = new TextDecoder().decode(decrypted);
    return JSON.parse(text);
  }

  global.Encapsula = {
    isEncrypted: isEncrypted,
    decode: decode,
  };
})(typeof window !== "undefined" ? window : globalThis);
