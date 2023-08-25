const MONEY_SAVER_EXPORT_ACCOUNT_INSTRUMENT = {
  header: 'MoneySaver Export Account',
  listHeader: '',
  listDescription: '',
  slug: 'moneysaverexportaccount',
  leafList: [
    {
      header: '',
      listHeader: 'Local Currency Bank Transfer',
      listDescription:
        'Setup a local account in all locations mentioned below to accept international payments',
      slug: 'localcurrencytransfer',
      list: [],
    },
    {
      header: '',
      listHeader: 'International Bank Transfer',
      listDescription: 'Set up a SWIFT account to accept payments in various currencies',
      slug: 'swiftbanktransfer',
      list: [],
    },
  ],
};

const INTERNATIONAL_INSTRUMENT = {
  name: 'International Payments',
  description: 'Cards, Paypal, USD ACH & more',
  slug: 'international',
  icon: 'international',
  actionItems: {},
  leafList: [MONEY_SAVER_EXPORT_ACCOUNT_INSTRUMENT],
};

export const LEAF_LIST_TESTS = [
  {
    input: {
      initialState: {
        instrumentRequests: {
          leafInstrument: INTERNATIONAL_INSTRUMENT,
        },
        session: {
          user: {
            international: false,
          },
        },
      },
    },
    output: {
      header: MONEY_SAVER_EXPORT_ACCOUNT_INSTRUMENT.header,
    },
  },
  {
    input: {
      initialState: {
        instrumentRequests: {
          leafInstrument: INTERNATIONAL_INSTRUMENT,
        },
        session: {
          user: {
            international: true,
          },
        },
      },
    },
    output: {
      header: MONEY_SAVER_EXPORT_ACCOUNT_INSTRUMENT.header,
    },
  },
];
