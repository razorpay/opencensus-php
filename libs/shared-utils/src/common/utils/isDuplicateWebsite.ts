/**
 * Normalizes a website URL by removing the protocol, www prefix, and trailing slashes,
 * while preserving query parameters.
 *
 * @param {string} url - The URL to normalize.
 * @returns {string | null} - The normalized URL, or null if the URL is invalid.
 */

export const getCanonicalUrl = (url: string) => {
  // Return null for empty strings
  if (!url || url.trim() === '') {
    return null;
  }

  try {
    // Try to create a proper URL object
    const parsedUrl = new URL(url.startsWith('http') ? url.trim() : `https://${url.trim()}`);

    // Check if hostname is valid (contains at least one dot)
    if (!parsedUrl.hostname.includes('.')) {
      return null;
    }

    let hostname = parsedUrl.hostname.toLowerCase().trim();
    if (hostname.startsWith('www.')) {
      hostname = hostname.replace('www.', '');
    }
    const path = parsedUrl.pathname.replace(/\/+$/, ''); // Remove trailing slash
    const query = parsedUrl.search || ''; // Include query string
    return `${hostname}${path}${query}`;
  } catch {
    return null;
  }
};

/**
 * Checks if a new website URL is already in the list of existing websites.
 *
 * @param {string[] | string} existingWebsites - The list of existing websites or a single website URL.
 * @param {string} newWebsite - The new website URL to check.
 * @returns {boolean} - Returns true if the new website is already in the list/matches the existing site, false otherwise.
 */

export const isDuplicateWebsite = (existingWebsites: string[] | string, newWebsite: string) => {
  const newNormalized = getCanonicalUrl(newWebsite);
  if (!newNormalized) return false;

  return (typeof existingWebsites === 'string' ? [existingWebsites] : existingWebsites).some(
    (site) => {
      const normalized = getCanonicalUrl(site);
      return normalized === newNormalized;
    },
  );
};
