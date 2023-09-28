export const redirectToAppRoute = (to: string) => {
  if (window.location.pathname === '/') {
    window.history.replaceState('', '', to);
  }
};
