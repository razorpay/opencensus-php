export const ActiveAccountData = {
  title: 'Active bank account',
  description:
    'This is the bank account where settlements are processed and collected payments will be deposited in',
};

export const PreviousAccountData = {
  title: 'Previously used bank accounts',
  description:
    'These are bank accounts you’ve used for settlements before. You can switch to using any of them as the active bank account anytime',
};

export const action = {
  title: 'Switch to active',
  isDisable: true,
};

export const BANK_DATA = [
  {
    id: 'name',
    title: 'Beneficiary name',
  },
  {
    id: 'account_number',
    title: 'Account number',
  },
  {
    id: 'ifsc',
    title: 'IFSC code',
  },
  {
    id: 'updated_at',
    title: 'Updated on',
  },
];

export const RETRY_LIMIT = 1;
