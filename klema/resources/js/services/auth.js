const TOKEN_STORAGE_KEY = 'klema.apiToken';

export function getApiToken() {
  if (typeof window === 'undefined') {
    return null;
  }

  return window.localStorage?.getItem(TOKEN_STORAGE_KEY) ?? null;
}

export function setApiToken(token) {
  if (typeof window === 'undefined') {
    return;
  }

  if (!token) {
    window.localStorage?.removeItem(TOKEN_STORAGE_KEY);
  } else {
    window.localStorage?.setItem(TOKEN_STORAGE_KEY, token);
  }
}

export async function ensureApiToken(axiosInstance) {
  const existingToken = getApiToken();

  if (existingToken) {
    return existingToken;
  }

  // Don't try to get token from /api/auth/token as it requires session auth
  // This endpoint is only for session-authenticated users to get API tokens
  // For SPA authentication, users should use /api/auth/login to get tokens directly
  return null;
}

export function revokeApiToken() {
  setApiToken(null);
}
