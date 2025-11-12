import { ensureApiToken, getApiToken, setApiToken } from './auth';

async function performFetch(url, options, token) {
  const headers = new Headers(options.headers || {});
  headers.set('Accept', headers.get('Accept') || 'application/json');

  if (token && !headers.has('Authorization')) {
    headers.set('Authorization', `Bearer ${token}`);
  }

  const init = {
    credentials: 'include',
    ...options,
    headers,
  };

  if (init.body && typeof init.body === 'object' && !(init.body instanceof FormData)) {
    if (!headers.has('Content-Type')) {
      headers.set('Content-Type', 'application/json');
    }

    if (headers.get('Content-Type')?.includes('application/json')) {
      init.body = JSON.stringify(init.body);
    }
  }

  return fetch(url, init);
}

export async function authorizedFetch(url, options = {}) {
  const initialToken = getApiToken();
  let response = await performFetch(url, options, initialToken);

  if (response.status !== 401) {
    return response;
  }

  setApiToken(null);
  const refreshedToken = await ensureApiToken();

  if (!refreshedToken) {
    return response;
  }

  response = await performFetch(url, options, refreshedToken);

  return response;
}
