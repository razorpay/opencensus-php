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

export const REVERSAL_TYPES = {
  WALLET_LOAD: 'WALLET_LOAD',
  GIFT_CARD: 'GIFT_CARD',
};

export const REVERSAL_TYPE_MESSAGE = {
  [REVERSAL_TYPES.WALLET_LOAD]: [
    'Load ID can be obtained from the response file if you have used batch load action or from transactions tab on dashboard.',
    'This feature will work only if you are not using two factor authentication for wallet debit',
  ],
  [REVERSAL_TYPES.GIFT_CARD]: [
    'Either the gift card ID or the gift card number must be present for a successful update.',
    'Reason for cancellation, contact (email/phone) of the customer making the extension request, link to the support ticket with customer request details, timestamp of the request and source of the request (email, social media, chat etc.) are mandatory parameters.',
  ],
};

export const REVERSAL_TYPES_OPTIONS = [
  { label: 'Reverse Loads', name: REVERSAL_TYPES.WALLET_LOAD },
  { label: 'Cancel Gift Cards', name: REVERSAL_TYPES.GIFT_CARD },
];

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
  UPDATE_GIFT_CARD_EXPIRY: 'update_gift_cards_expiry',
  CREATE_GIFT_CARD_TRANSFERS: 'create_gift_card_transfers',
  CANCEL_BULK_GIFT_CARDS: 'cancel_bulk_gift_cards',
};
