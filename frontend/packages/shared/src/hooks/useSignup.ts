import { useState } from "react";
import { signupService } from "../services/auth";
import type { SignupPayload } from "../types/auth";

type SignupError = {
  status?: number;
  data?: { message?: string; violations?: unknown[] };
  message?: string;
};

export type SignupResult = { ok: true } | { ok: false; error: SignupError };

export function useSignup() {
  const [loading, setLoading] = useState(false);

  async function signup(payload: SignupPayload): Promise<SignupResult> {
    setLoading(true);
    try {
      console.log(payload);

      await signupService(payload);
      return { ok: true };
    } catch (err) {
      console.log(err);
      return { ok: false, error: err as SignupError };
    } finally {
      setLoading(false);
    }
  }

  return { signup, loading };
}
