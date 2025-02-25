export const ANALYTICS = {
  SCREEN: { DASHBOARD: 'dashboard' },
  OBJECT: { I18N: 'i18n' },
  ACTION: {
    PHONE_NUMBER: 'phone number',
    CURRENCY: 'currency',
    GEO: 'geo',
  },
};

export const zIndicesMap = {
  bottomSheet: 100,
  modal: 1000,
  modalOverlay: 1001,
  drawer: 1001,
  dropdownOverlay: 1002,
  tourMask: 1100,
  popover: 1100,
  tooltip: 1100,
  sidebar: 1000,
  sidebarBgOverlay: 1000,
};

// To add in this list for all the query cache keys to avoid name collision
export const REACT_QUERY_CACHE_KEYS = {
  STORES_STATES_AND_CITIES_DATA: 'stores_states_and_cities_data',
  STORES_LIST_DATA: 'stores_list_data',
  STORE_INFO: 'store_info',
  STORE_TERMINALS: 'store-terminals',
  DELETED_STORES_DATA: 'deleted_stores_data',
};
