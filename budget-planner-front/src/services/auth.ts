// services/auth.ts
export type LoginPayload = {
  email: string;
  password: string;
};

export type RegisterPayload = {
  firstName: string;
  email: string;
  password: string;
  invitationCode: string;
};

export type AuthUser = {
  id: string;
  firstName: string;
  email: string;
};

const MOCK_DELAY_MS = 600;

function wait(durationMs: number): Promise<void> {
  return new Promise(function resolveAfterDelay(resolve) {
    setTimeout(resolve, durationMs);
  });
}

export async function login(payload: LoginPayload): Promise<AuthUser> {
  await wait(MOCK_DELAY_MS);

  if (payload.password.length < 6) {
    throw new Error("Adresse e-mail ou mot de passe incorrect.");
  }

  return {
    id: 'mock-user-id',
    firstName: 'Loriana',
    email: payload.email,
  };
}

export async function register(payload: RegisterPayload): Promise<AuthUser> {
  await wait(MOCK_DELAY_MS);

  if (payload.invitationCode.trim().length === 0) {
    throw new Error("Le code d'invitation du foyer est requis.");
  }

  return {
    id: 'mock-user-id',
    firstName: payload.firstName,
    email: payload.email,
  };
}
