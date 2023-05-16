export const LOAD_TYPE = [
  { label: 'Accounts Load', name: 'accounts' },
  { label: 'Container Load', name: 'container' },
];

export const WALLET_LOAD_TYPES = {
  ACCOUNTS: 'accounts',
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
};
