/**
 * Checks if a user is exclusively a Bill Me user by verifying they have exactly one merchant
 * with the product type 'billing'.
 *
 * @param {Record<string, any>} user - The user object containing merchant information.
 * @returns {boolean} - Returns true if the user is exclusively a Bill Me user, false otherwise.
 */

export function isBillMeOnlyUser(user: Record<string, any>): boolean {
  const merchantKeys = Object.keys(user?.merchants ?? {});
  return merchantKeys.length === 1 && user?.merchants?.[merchantKeys[0]]?.product === 'billing';
}
