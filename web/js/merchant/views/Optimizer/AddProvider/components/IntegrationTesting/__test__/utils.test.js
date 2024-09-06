import { Badge, CheckIcon, CloseIcon, InfoIcon } from '@razorpay/blade/components';
import {
  getInstrumentCoverageTabsList,
  getCardCoverageData,
  getCardCoverageColumns,
  exportedForTesting,
} from 'merchant/views/Optimizer/AddProvider/components/IntegrationTesting/utils';

import { RAZORPAY_COVERAGE, GATEWAY_COVERAGE, PAYTM_GATEWAY_COVERAGE } from './mocks';

describe('Optimizer IntegrationTesting IntegrationAuditSummary Utils', () => {
  const { CoverageBadge } = exportedForTesting;
  const coverageBadge = CoverageBadge;

  test('CoverageBadge', () => {
    let result = coverageBadge({ status: 'positive' });
    expect(result).toEqual(
      <Badge icon={CheckIcon} color="positive">
        Covered
      </Badge>,
    );
    result = coverageBadge({ status: 'negative' });
    expect(result).toEqual(
      <Badge icon={CloseIcon} color="negative">
        Not covered
      </Badge>,
    );
    result = coverageBadge({ status: 'notice' });
    expect(result).toEqual(
      <Badge icon={InfoIcon} color="notice">
        Unknown
      </Badge>,
    );
  });

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

  test('getCardCoverageData', () => {
    const result = getCardCoverageData('paytm', PAYTM_GATEWAY_COVERAGE, RAZORPAY_COVERAGE);
    expect(result).toEqual([
      {
        cardNetwork: 'MC',
        cardType: 'debitType',
        gatewayCoverage: 'unkown',
        razorpayCoverage: true,
      },
      {
        cardNetwork: 'MAES',
        cardType: 'debitType',
        gatewayCoverage: 'unkown',
        razorpayCoverage: true,
      },
      {
        cardNetwork: 'RUPAY',
        cardType: 'debitType',
        gatewayCoverage: 'unkown',
        razorpayCoverage: true,
      },
      {
        cardNetwork: 'VISA',
        cardType: 'debitType',
        gatewayCoverage: 'unkown',
        razorpayCoverage: true,
      },
      {
        cardNetwork: 'VISA',
        cardType: 'creditType',
        gatewayCoverage: 'unkown',
        razorpayCoverage: true,
      },
      {
        cardNetwork: 'MC',
        cardType: 'creditType',
        gatewayCoverage: 'unkown',
        razorpayCoverage: true,
      },
      {
        cardNetwork: 'RUPAY',
        cardType: 'creditType',
        gatewayCoverage: 'unkown',
        razorpayCoverage: true,
      },
      {
        cardNetwork: 'DICL',
        cardType: 'creditType',
        gatewayCoverage: 'unkown',
        razorpayCoverage: true,
      },
    ]);
  });

  test('getCardCoverageColumns', () => {
    const cardColumns = getCardCoverageColumns('paytm');
    cardColumns.forEach((item) => {
      let result;
      if (item.label === 'Card Type') {
        result = item.value({ cardType: 'debitType' });
        expect(result).toBe('Debit Card');
        result = item.value({ cardType: 'creditType' });
        expect(result).toBe('Credit Card');
        result = item.value({ cardType: 'prepaidType' });
        expect(result).toBe('Prepaid Card');
      } else if (item.label === 'Card Network') {
        result = item.value({ cardNetwork: 'VISA' });
        expect(result).toBe('Visa Cards');
        result = item.value({ cardNetwork: 'MC' });
        expect(result).toBe('Mastercard');
        result = item.value({ cardNetwork: 'RUPAY' });
        expect(result).toBe('Rupay Cards');
        result = item.value({ cardNetwork: 'MAES' });
        expect(result).toBe('Maestro Cards');
        result = item.value({ cardNetwork: 'AMEX' });
        expect(result).toBe('American Express Cards');
        result = item.value({ cardNetwork: 'DICL' });
        expect(result).toBe('Diners Club Cards');
        result = item.value({ cardNetwork: 'JCB' });
        expect(result).toBe('Japan Credit Bureau Cards');
        result = item.value({ cardNetwork: 'BAJAJ' });
        expect(result).toBe('Bajaj Finserv Cards');
      } else if (item.label === 'On Razorpay') {
        result = item.value({ razorpayCoverage: true });
        expect(result).toEqual(<CoverageBadge status="positive" />);
        result = item.value({ razorpayCoverage: false });
        expect(result).toEqual(<CoverageBadge status="negative" />);
      } else if (item.label === 'On Paytm') {
        result = item.value({ gatewayCoverage: true });
        expect(result).toEqual(<CoverageBadge status="positive" />);
        result = item.value({ gatewayCoverage: false });
        expect(result).toEqual(<CoverageBadge status="negative" />);
        result = item.value({ gatewayCoverage: 'unkown' });
        expect(result).toEqual(<CoverageBadge status="notice" />);
      }
    });
  });
});
