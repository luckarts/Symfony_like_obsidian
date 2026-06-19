import type { SignupPayload, SignupResponse } from "../types/auth";

const API_ENDPOINTS = {
  SIGNUP: "/api/users",
} as const;

export const signupService = async (
  payload: SignupPayload,
  apiBase = "",
): Promise<SignupResponse> => {
  //const [firstName, ...rest] = payload.name.trim().split(' ')
  //const lastName = rest.join(' ') || firstName
  const res = await fetch(`${apiBase}${API_ENDPOINTS.SIGNUP}`, {
    method: "POST",
    headers: { "Content-Type": "application/ld+json" },
    body: JSON.stringify({
      firstName: payload.firstName,
      lastName: payload.lastName,
      email: payload.email,
      password: payload.password,
    }),
  });

  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    const err = Object.assign(new Error(data.message ?? res.statusText), {
      status: res.status,
      data,
    });
    throw err;
  }

  return res.json();
};
