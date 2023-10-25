/**
 * Handle edge cases where requested route doesn't have basename
 * @see https://github.com/remix-run/react-router/issues/8427#issuecomment-1056988913
 */
export const redirectToAppRoute = (to: string): void => {
  if (
    window.location.pathname === '/' ||
    window.location.pathname === '/signin' ||
    window.location.pathname === '/signup'
  ) {
    window.history.replaceState('', '', to);
  }
};
