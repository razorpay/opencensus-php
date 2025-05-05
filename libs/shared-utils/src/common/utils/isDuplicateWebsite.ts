/**
 * Checks if a new website URL is already in the list of existing websites.
 *
 * @param {string[] | string} existingWebsites - The list of existing websites or a single website URL.
 * @param {string} newWebsite - The new website URL to check.
 * @returns {boolean} - Returns true if the new website is already in the list/matches the existing site, false otherwise.
 */

import { getCanonicalUrl } from './getCanonicalUrl';

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
