export const ROUTES = {
  SIGNIN: 'signin',
  SIGNUP: 'signup',
};

export const BANK_NAMES = {
  ICICI: 'icic',
  HDFC: 'hdfc',
  BOB: 'bob',
  AXIS: 'axis',
};

export const getCookie = (name) => {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts[1].split(';')[0];
  return null;
};

export const getHostName = () => {
  return window.location.hostname;
};
