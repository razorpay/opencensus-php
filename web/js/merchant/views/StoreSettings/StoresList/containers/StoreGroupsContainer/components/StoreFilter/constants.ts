// TODO: Remove after StoresTableContainer changes added
export const STORES_SEARCH_BY_FIELDS = {
  STORE_NAME: {
    label: 'Store Name',
    value: 'STORE_NAME',
  },
  STORE_CODE: {
    label: 'Store Code',
    value: 'STORE_CODE',
  },
} as const;

export const STORES_LIMIT = 10;
export const STORE_NAME = 'STORE_NAME';
export const STORES_FETCH_ERROR_MESSAGE = 'Error in fetching stores. Please try again later.';
export const STORES_STATES_CITIES_ERROR_MESSAGE =
  'Error in fetching states and cities. Please try again later.';
export const STORES_STATES_CITIES_LOADER = 'Stores States and Cities list loading';
export const STORES_LIST_LOADER = 'Stores list loading';
