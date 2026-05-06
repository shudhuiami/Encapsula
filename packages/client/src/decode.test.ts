import { describe, it, expect } from "vitest";
import { isEncapsulaEnvelope, decodeEncapsulaResponse } from "./decode";
import type { EncapsulaEnvelope } from "./types";

describe("isEncapsulaEnvelope", () => {
  it("returns true for valid envelope", () => {
    const envelope: EncapsulaEnvelope = {
      encrypted: true,
      payload: "abc",
      iv: "def",
      tag: "ghi",
      alg: "AES-256-GCM",
    };
    expect(isEncapsulaEnvelope(envelope)).toBe(true);
  });

  it("returns false for null", () => {
    expect(isEncapsulaEnvelope(null)).toBe(false);
  });

  it("returns false for plain object", () => {
    expect(isEncapsulaEnvelope({ name: "Ahmed" })).toBe(false);
  });

  it("returns false for string", () => {
    expect(isEncapsulaEnvelope("hello")).toBe(false);
  });

  it("returns false when encrypted is false", () => {
    expect(
      isEncapsulaEnvelope({
        encrypted: false,
        payload: "a",
        iv: "b",
        tag: "c",
        alg: "d",
      })
    ).toBe(false);
  });

  it("returns false when fields are missing", () => {
    expect(isEncapsulaEnvelope({ encrypted: true, payload: "a" })).toBe(false);
  });
});

describe("decodeEncapsulaResponse", () => {
  it("passes through non-encrypted data unchanged", async () => {
    const data = { users: [{ id: 1, name: "Ahmed" }] };
    const result = await decodeEncapsulaResponse(data, { key: "unused" });
    expect(result).toEqual(data);
  });

  it("passes through primitive values", async () => {
    expect(await decodeEncapsulaResponse("hello", { key: "k" })).toBe("hello");
    expect(await decodeEncapsulaResponse(42, { key: "k" })).toBe(42);
    expect(await decodeEncapsulaResponse(null, { key: "k" })).toBe(null);
  });

  it("throws on invalid key length", async () => {
    const envelope: EncapsulaEnvelope = {
      encrypted: true,
      payload: "abc",
      iv: "def",
      tag: "ghi",
      alg: "AES-256-GCM",
    };

    await expect(
      decodeEncapsulaResponse(envelope, { key: btoa("short") })
    ).rejects.toThrow("Invalid key length");
  });
});
