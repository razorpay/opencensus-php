export const TERMINAL_STATUS_OPTIONS = {
  ALL: {
    label: 'All',
    value: 'ALL',
  },
  ON: {
    label: 'On',
    value: 'ON',
  },
  OFF: {
    label: 'Off',
    value: 'OFF',
  },
} as const;

export const TERMINAL_BIT_OPTIONS = {
  BIT_32: '32',
  BIT_64: '64',
};

export const TERMINALS_SEARCH_BY_FIELDS = {
  TERMINAL_NAME: {
    label: 'Terminal Name',
    value: 'TERMINAL_NAME',
  },
  MAC_ADDRESS: {
    label: 'MAC',
    value: 'MAC_ADDRESS',
  },
  IP_ADDRESS: {
    label: 'IP Address',
    value: 'IP_ADDRESS',
  },
  VERSION: {
    label: 'Version',
    value: 'VERSION',
  },
} as const;

export const ACTIVE_TERMINAL_INFO_INIT_VALUE = { id: '', showModal: false };
