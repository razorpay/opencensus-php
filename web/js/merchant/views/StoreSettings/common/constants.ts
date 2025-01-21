export const STORE_TYPE_MAP = {
  ALL: {
    label: 'All',
    color: 'positive',
    value: 'ALL',
  },
  OFFLINE: {
    label: 'Offline',
    color: 'positive',
    value: 'OFFLINE',
  },
  ONLINE: {
    label: 'Online',
    color: 'information',
    value: 'ONLINE',
  },
} as const;

export const TERMINAL_BIT_OPTIONS = {
  BIT_32: '32',
  BIT_64: '64',
};

// Max limit for pagination
export const MAX_LIMIT = 25;

export const DIGITAL_BILLING = 'DIGITAL_BILLING';
export const ONLINE = 'ONLINE';
export const OFFLINE = 'OFFLINE';
