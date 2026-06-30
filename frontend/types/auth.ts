export interface SignupPayload {
  firstName: string
  lastName: string
  email: string
  password: string
}

export interface AuthUser {
  id: string
  email: string
  firstName: string
  lastName: string
}

export interface SignupResponse {
  id: string
  email: string
  firstName: string
  lastName: string
  roles: string[]
  createdAt: string
  updatedAt: string
}
