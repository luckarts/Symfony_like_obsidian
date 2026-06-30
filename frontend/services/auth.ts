import type { SignupPayload, SignupResponse } from "~/types/auth";

const API_ENDPOINTS = {
  SIGNUP: "/api/users",
} as const;

export const signupService = (
  payload: SignupPayload,
): Promise<SignupResponse> => {
  return $fetch<SignupResponse>(API_ENDPOINTS.SIGNUP, {
    method: "POST",
    headers: { "Content-Type": "application/ld+json" },
    body: { ...payload },
  });
};
