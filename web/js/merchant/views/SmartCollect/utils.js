export const checkIfVirtualAccountRoute = (match, location) => {
  if (location.pathname.includes('/virtualaccounts')) {
    return true;
  }
  return false;
};
