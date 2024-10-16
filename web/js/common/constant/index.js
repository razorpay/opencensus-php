// Todo: delete this file, it's available in @dashboard/shared-utils
export const SENSITIVE_FIELDS = [
  'customer_contact',
  'customer_email',
  'cust_contact',
  'cust_email',
  'contact',
  'email',
];

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
