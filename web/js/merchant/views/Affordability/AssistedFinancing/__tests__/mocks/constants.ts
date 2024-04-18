export const cardlessEmiPaymentLinkData = {
  method: 'cardless_emi',
  title: 'Liquiloans Cardless EMI',
  name: 'Pay using LIQUILOANS',
  provider: 'liquiloans',
  image: 'dist/images/liquiloans.df4797acedf634bf.svg',
  notEligible: false,
};

export const emiPaymentLinkData = {
  method: 'emi',
  title: 'UTIB Credit Card EMI',
  name: 'Pay using UTIB Credit Card',
  image: 'dist/images/axis.7ff000fefeaeed82.png',
  provider: 'utib',
  type: 'credit',
  notEligible: false,
  emiPlan: [
    {
      emiPlan: '₹ 6,091.40 x 3 m',
      interest: '₹ 374.20 (12.5%)',
      totalPayable: '₹ 18,274.20',
    },
    {
      emiPlan: '₹ 3,115.20 x 6 m',
      interest: '₹ 791.20 (15%)',
      totalPayable: '₹ 18,691.20',
    },
    {
      emiPlan: '₹ 2,141.01 x 9 m',
      interest: '₹ 1,369.09 (18%)',
      totalPayable: '₹ 19,269.09',
    },
    {
      emiPlan: '₹ 1,666.73 x 12 m',
      interest: '₹ 2,100.76 (21%)',
      totalPayable: '₹ 20,000.76',
    },
  ],
};

export const inEligibleEmiPaymentLinkData = {
  method: 'emi',
  title: 'UTIB Credit Card EMI',
  name: 'Pay using UTIB Credit Card',
  image: 'https://localhost:8080/public/dist/images/axis.7ff000fefeaeed82.png',
  provider: 'utib',
  type: 'credit',
  notEligible: true,
  emiPlan: [],
};

export const MockGetCardEmiResponse = [
  {
    method: 'emi',
    title: 'HDFC Credit Cards',
    name: 'Pay using HDFC',
    image: 'HDFC.gif',
    provider: 'hdfc',
    type: 'credit',
    notEligible: false,
    emiPlan: [
      {
        emiPlan: '₹ 111 x 9 m',
        interest: '₹ 0 (@ 0.12%)',
        totalPayable: '₹ 1,000',
      },
      {
        emiPlan: '₹ 333 x 3 m',
        interest: '₹ 0 (@ 0.12%)',
        totalPayable: '₹ 1,000',
      },
    ],
  },
  {
    method: 'emi',
    title: 'HDFC Debit Cards',
    name: 'Pay using HDFC',
    image: 'HDFC.gif',
    provider: 'hdfc',
    type: 'debit',
    notEligible: true,
    emiPlan: [
      {
        emiPlan: '₹ 111 x 9 m',
        interest: '₹ 0 (@ 0.12%)',
        totalPayable: '₹ 1,000',
      },
      {
        emiPlan: '₹ 340 x 3 m',
        interest: '₹ 20 (@ 12.5%)',
        totalPayable: '₹ 1,020',
      },
    ],
  },
];
