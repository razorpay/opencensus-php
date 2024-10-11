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

const RECURRING_METHODS = {
  UPI: {
    name: 'UPI/QR',
    description: 'GooglePay, PhonePe, BHIM & more',
    slug: 'upi',
    icon: 'upi',
    actionItems: {},
    leafList: [
      {
        header: 'UPI',
        docLink: 'https://razorpay.com/docs/payment-gateway/payment-methods/upi',
        list: [
          {
            name: 'UPI',
            status: 'greyed',
            slug: 'upi',
            description: (
              <p>
                Gpay, Phonepe, Paytm, and{' '}
                <a
                  href="https://www.npci.org.in/what-we-do/upi/3rd-party-apps"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  more...
                </a>
              </p>
            ),
          },
        ],
      },
      {
        header: 'UPI Autopay',
        list: [
          {
            name: 'UPI Autopay',
            status: 'greyed',
            slug: 'recurring.autopay',
            description: 'Allow customers to create mandates via UPI.',
          },
        ],
      },
    ],
  },
  CARD: {
    name: 'Cards',
    description: 'Visa, Master, Amex',
    actionItems: {},
    slug: 'cards',
    icon: 'card',
    leafList: [
      {
        header: 'Domestic Cards',
        list: [
          {
            name: 'Visa Cards',
            status: 'greyed',
            slug: 'domestic.visa',
            icon: 'visa',
          },
          {
            name: 'MasterCard',
            status: 'greyed',
            slug: 'domestic.mastercard',
            icon: 'masterCard',
          },
          {
            name: 'Rupay Cards',
            status: 'greyed',
            slug: 'domestic.rupay',
            icon: 'rupay',
          },
          {
            name: 'Amex Cards',
            status: 'greyed',
            slug: 'domestic.amex',
            icon: 'amex',
          },
          {
            name: 'Diners Club',
            status: 'greyed',
            slug: 'domestic.dicl',
            icon: 'diners',
          },
          {
            name: 'Maestro',
            status: 'greyed',
            slug: 'domestic.maestro',
            icon: 'maestro',
          },
        ],
      },
      {
        header: 'Cards Recurring',
        list: [
          {
            name: 'Cards Recurring',
            description: 'Allow customers to create mandates via cards.',
            status: 'greyed',
            slug: 'recurring',
            icon: '',
          },
        ],
      },
    ],
  },
  EMANDATE: {
    name: 'Netbanking',
    description: 'All Indian Banks',
    slug: 'netbanking',
    icon: 'netbanking',
    actionItems: {},
    intermediateList: [
      {
        name: 'Retail Netbanking',
        description: 'Direct Netbanking with customers',
        slug: 'retail',
        leafList: [
          {
            header: 'Available Banks',
            list: [],
          },
        ],
      },
      {
        name: 'Corporate Netbanking',
        description: 'Netbanking with businesses and corporates',
        slug: 'corporate',
        leafList: [
          {
            header: 'Available Banks',
            list: [],
          },
        ],
      },
      {
        name: 'E-Mandate',
        description:
          'Allow customers to create mandates via netbanking, debit card, eSign or paper NACH',
        slug: 'recurring',
        leafList: [
          {
            header: 'Available Methods',
            list: [],
          },
        ],
      },
    ],
  },
};

const MORE_INTERNATIONAL_METHODS = {
  name: 'More international payment methods',
  description:
    'Includes international bank transfer, local currency bank transfer and instant bank transfer',
  slug: 'moreinternationalmethods',
  icon: 'moreinternationalmethods',
  actionItems: {},
  leafList: [],
};

const INTERNATIONAL_INSTRUMENT = {
  name: 'International Payments',
  description: 'Cards, Paypal, USD ACH & more',
  slug: 'international',
  icon: 'international',
  actionItems: {},
  leafList: [MONEY_SAVER_EXPORT_ACCOUNT_INSTRUMENT, MORE_INTERNATIONAL_METHODS],
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
  {
    input: {
      initialState: {
        instrumentRequests: {
          leafInstrument: RECURRING_METHODS.UPI,
        },
        session: {
          user: {
            international: true,
          },
        },
      },
    },
  },
  {
    input: {
      initialState: {
        instrumentRequests: {
          leafInstrument: RECURRING_METHODS.CARD,
        },
        session: {
          user: {
            international: false,
          },
        },
      },
    },
  },
  {
    input: {
      initialState: {
        instrumentRequests: {
          leafInstrument: RECURRING_METHODS.EMANDATE,
          intermediateInstrument: RECURRING_METHODS.EMANDATE,
        },
        session: {
          user: {
            international: false,
          },
        },
      },
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
            isMoreInternationalMethodsEnabledForVAS: true,
          },
        },
      },
    },
    output: {
      header: MORE_INTERNATIONAL_METHODS.description,
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
            isMoreInternationalMethodsEnabledForVAS: false,
          },
        },
      },
    },
    output: {
      header: MORE_INTERNATIONAL_METHODS.name,
    },
  },
];
