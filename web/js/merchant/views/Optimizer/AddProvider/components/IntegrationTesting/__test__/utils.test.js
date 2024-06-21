import { getInstrumentCoverageTabsList } from 'merchant/views/Optimizer/AddProvider/components/IntegrationTesting/utils';

import { RAZORPAY_COVERAGE, GATEWAY_COVERAGE } from './mocks';

describe('Optimizer IntegrationTesting IntegrationAuditSummary Utils', () => {
  test('getInstrumentCoverageTabsList', () => {
    const expectedResult = {
      tabsList: [
        {
          label: 'Cards',
          value: 'card',
        },
        {
          label: 'UPI',
          value: 'upi',
        },
        {
          label: 'Netbanking',
          value: 'netbanking',
        },
        {
          label: 'Wallets',
          value: 'wallet',
        },
        {
          label: 'Others',
          value: 'others',
        },
      ],
      otherMethods: ['emi', 'e-mandate', 'sodexo'],
    };
    const result = getInstrumentCoverageTabsList(RAZORPAY_COVERAGE, GATEWAY_COVERAGE);
    expect(result).toEqual(expectedResult);
  });
});
