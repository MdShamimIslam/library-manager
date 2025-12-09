const { rest_url, nonce } = LM_SETTINGS;

const headers = {
  'Content-Type': 'application/json',
  'X-WP-Nonce': nonce,
};

export const fetchBooks = async () => {
  const res = await fetch(`${rest_url}/books`, { headers });
  return res.json();
};

export const fetchBook = async (id) => {
  const res = await fetch(`${rest_url}/books/${id}`, { headers });
  return res.json();
};

export const createBook = async (data) => {
  const res = await fetch(`${rest_url}/books`, {
    method: 'POST',
    headers,
    body: JSON.stringify(data),
  });
  return res.json();
};

export const updateBook = async (id, data) => {
  const res = await fetch(`${rest_url}/books/${id}`, {
    method: 'PUT',
    headers,
    body: JSON.stringify(data),
  });
  return res.json();
};

export const deleteBook = async (id) => {
  const res = await fetch(`${rest_url}/books/${id}`, {
    method: 'DELETE',
    headers,
  });
  return res.ok;
};
