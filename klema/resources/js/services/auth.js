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

  try {
    const axiosClient = axiosInstance ?? (await import('axios')).default;
    await axiosClient.get('/sanctum/csrf-cookie', { withCredentials: true });
    const { data } = await axiosClient.post('/api/auth/token', {}, {
      withCredentials: true,
      headers: {
        'Accept': 'application/json'
      }
    });

    if (data?.token) {
      setApiToken(data.token);
      return data.token;
    }
  } catch (error) {
    console.warn('Unable to issue API token', error);
  }

  return null;
}

export function revokeApiToken() {
  setApiToken(null);
}
