const API_ERROR = {
    error: 'An error occured while fetching data from the server',
  },
  API_INVALID_RESP = {
    error: 'Got unexpected response from the server',
  },
  OLDEST_TXN_ERROR = {
    error: 'Unable to get your first transaction date'
  },
  isMobileDevice = window.outerWidth <= 768;

export {
  API_ERROR,
  API_INVALID_RESP,
  OLDEST_TXN_ERROR,
  isMobileDevice
};
