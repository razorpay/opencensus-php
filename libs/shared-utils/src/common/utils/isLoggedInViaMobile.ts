/**
 * Checks if the user is logged in via mobile by verifying the value in localStorage.
 *
 * @returns {boolean} - Returns true if the user is logged in via mobile, false otherwise.
 */
export const isLoggedInViaMobile = (): boolean => {
    return localStorage?.getItem('loggedInVia') === 'contact_mobile';
  };
  