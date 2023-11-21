export const CREATE_LOADS_OPTIONS = [
  { label: 'Accounts Load', name: 'account' },
  { label: 'Container Load', name: 'container' },
];

export const CREATE_ACCOUNTS_OPTIONS = [
  { label: 'Create Wallet Accounts', name: 'account' },
  { label: 'Create User Containers', name: 'user' },
  { label: 'Create Gift Cards', name: 'gift_cards' },
];

export const ACCOUNT_TYPES = {
  ACCOUNT: 'account',
  CONTAINER: 'user',
  GIFT_CARDS: 'gift_cards',
};

export const LOAD_TYPES = {
  ACCOUNT: 'account',
  CONTAINER: 'container',
};

export const DISPLAY_MESSAGES = {
  process: 'The file is being processed. Please wait as this may take some time.',
  success: 'The file has been processed successfully.',
  error: 'There was an error while processing the file. Please try again after some time.',
  exceed: 'The file size exceeds the maximum size limit. Please upload a smaller file.',
};

export const BATCH_TYPES = {
  CREATE_WALLET_CONTAINER_LOADS: 'create_wallet_container_loads',
  CREATE_WALLET_LOADS: 'create_wallet_loads',
  CREATE_WALLET_ACCOUNTS: 'create_wallet_accounts',
  CREATE_WALLET_USERS_CONTAINERS: 'create_wallet_user_containers',
  CREATE_WALLET_REVERSAL_CONTAINERS: 'create_wallet_reversal_containers',
  CREATE_BULK_GIFT_CARDS: 'create_bulk_gift_cards',
};
