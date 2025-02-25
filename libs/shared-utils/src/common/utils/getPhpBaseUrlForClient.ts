export const getPhpBaseUrlForClient = () => {
  /**
   * Extracts the current domain and protocol from the window location object.
   */
  const domain = window.location.hostname;
  const protocol = window.location.protocol;

  return domain === 'localhost' ? 'https://localhost:8888' : `${protocol}//${domain}`;
};
