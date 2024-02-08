export const isMoneySaverAccountsActivatedTests = [
  [undefined, false],
  [{ accountsDeactivated: true, isLoading: false, data: [] }, true],
  [{ accountsDeactivated: false, isLoading: true, data: [] }, true],
  [{ accountsDeactivated: false, isLoading: false, data: [] }, false],
  [
    {
      accountsDeactivated: false,
      isLoading: false,
      data: [{ va_currency: 'USD' }, { va_currency: 'SWIFT' }],
    },
    true,
  ],
  [
    {
      accountsDeactivated: false,
      isLoading: false,
      data: [{ va_currency: 'EUR' }, { va_currency: 'GBP' }],
    },
    false,
  ],
];

export const isInstantBankTransferActivatedTests = [
  [undefined, false],
  [{ slug: 'notInternational' }, false],
  [{ slug: 'international' }, false],
  [
    {
      slug: 'international',
      leafList: [{ slug: 'instantbanktransfer', list: [{ status: 'requestable' }] }],
    },
    false,
  ],
  [
    {
      slug: 'international',
      leafList: [
        {
          slug: 'instantbanktransfer',
          list: [{ status: 'something_else' }, { status: 'another_status' }],
        },
      ],
    },
    true,
  ],
];
