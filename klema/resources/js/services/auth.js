const TOKEN_STORAGE_KEY = 'klema.apiToken';
const TOKEN_META_STORAGE_KEY = 'klema.apiToken.meta';
const REFRESH_BUFFER_SECONDS = 60;
const MIN_REFRESH_DELAY_MS = 15_000;

let refreshTimeoutId = null;

const isBrowser = () => typeof window !== 'undefined' && typeof window.localStorage !== 'undefined';

function clearRefreshTimer() {
  if (refreshTimeoutId) {
    clearTimeout(refreshTimeoutId);
    refreshTimeoutId = null;
  }
}

function loadStoredMeta() {
  if (!isBrowser()) {
    return null;
  }

  const raw = window.localStorage.getItem(TOKEN_META_STORAGE_KEY);

  if (!raw) {
    return null;
  }

  try {
    const parsed = JSON.parse(raw);

    if (parsed && typeof parsed === 'object') {
      return parsed;
    }
  } catch (error) {
    console.warn('Failed to parse token metadata', error);
  }

  return null;
}

function persistMeta(meta) {
  if (!isBrowser()) {
    return;
  }

  if (!meta) {
    window.localStorage.removeItem(TOKEN_META_STORAGE_KEY);
    return;
  }

  window.localStorage.setItem(TOKEN_META_STORAGE_KEY, JSON.stringify(meta));
}

function normalizeMeta(meta = {}) {
  if (!meta || typeof meta !== 'object') {
    return {};
  }

  const normalized = {};

  if (typeof meta.expiresIn === 'number' && Number.isFinite(meta.expiresIn)) {
    normalized.expiresAt = Date.now() + meta.expiresIn * 1000;
  } else if (meta.expiresAt) {
    const expiresAt = Number(meta.expiresAt);
    normalized.expiresAt = Number.isFinite(expiresAt) ? expiresAt : undefined;
  }

  if ('remember' in meta) {
    normalized.remember = Boolean(meta.remember);
  }

  if (Array.isArray(meta.abilities)) {
    normalized.abilities = meta.abilities;
  }

  return normalized;
}

function scheduleRefresh(meta) {
  clearRefreshTimer();

  if (!isBrowser() || !meta || !meta.expiresAt) {
    return;
  }

  const bufferMs = REFRESH_BUFFER_SECONDS * 1000;
  const delayMs = Math.max(meta.expiresAt - Date.now() - bufferMs, MIN_REFRESH_DELAY_MS);

  if (delayMs <= 0) {
    void refreshApiToken();
    return;
  }

  refreshTimeoutId = setTimeout(() => {
    void refreshApiToken();
  }, delayMs);
}

export function getApiToken() {
  if (!isBrowser()) {
    return null;
  }

  return window.localStorage.getItem(TOKEN_STORAGE_KEY) ?? null;
}

export function getApiTokenMeta() {
  return loadStoredMeta();
}

export function setApiToken(token, meta = {}) {
  if (!isBrowser()) {
    return;
  }

  if (!token) {
    window.localStorage.removeItem(TOKEN_STORAGE_KEY);
    persistMeta(null);
    clearRefreshTimer();
    return;
  }

  window.localStorage.setItem(TOKEN_STORAGE_KEY, token);

  const normalizedMeta = normalizeMeta(meta);
  persistMeta(normalizedMeta);
  scheduleRefresh(normalizedMeta);
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
      const abilities = Array.isArray(data.abilities) ? data.abilities : [];
      setApiToken(data.token, {
        expiresIn: typeof data.expires_in === 'number' ? data.expires_in : null,
        remember: abilities.includes('token:remember'),
        abilities,
      });

      return data.token;
    }
  } catch (error) {
    console.warn('Unable to issue API token', error);
  }

  return null;
}

export async function refreshApiToken() {
  const token = getApiToken();

  if (!token || !isBrowser()) {
    return null;
  }

  try {
    const response = await fetch('/api/token/refresh', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
      },
      credentials: 'include',
    });

    if (!response.ok) {
      if (response.status === 401 || response.status === 403) {
        revokeApiToken();
      }
      return null;
    }

    const data = await response.json();

    if (data?.token) {
      const abilities = Array.isArray(data.abilities) ? data.abilities : [];
      setApiToken(data.token, {
        expiresIn: typeof data.expires_in === 'number' ? data.expires_in : null,
        remember: abilities.includes('token:remember'),
        abilities,
      });

      return data.token;
    }
  } catch (error) {
    console.warn('Token refresh failed', error);
  }

  return null;
}

export function revokeApiToken() {
  setApiToken(null);
}

export function initializeTokenManager() {
  if (!isBrowser()) {
    return;
  }

  const token = getApiToken();
  const meta = getApiTokenMeta();

  if (!token) {
    clearRefreshTimer();
    return;
  }

  if (!meta) {
    clearRefreshTimer();
    return;
  }

  if (meta.expiresAt && meta.expiresAt <= Date.now()) {
    void refreshApiToken();
    return;
  }

  scheduleRefresh(meta);
}
