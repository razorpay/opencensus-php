import { screen } from '@testing-library/react';

import Amount from 'common/ui/Amount';
import { render } from 'test-utils';

const renderApp = (props) => {
  return render(<Amount {...props} />);
};

describe('Amount', () => {
  const positiveAmountProps = [
    { value: 1234567.579, currency: 'INR', hidePaisa: true, renderedOutput: '₹ 12,345' },
    { value: 1234567.579, currency: 'MYR', hidePaisa: false, renderedOutput: 'RM 12,345.68' },
    { value: 1234567.579, currency: 'SGD', hidePaisa: false, renderedOutput: '$ 12,345.68' },
    { value: 1234567.579, currency: 'USD', hidePaisa: false, renderedOutput: '$ 12,345.68' },
  ];

  positiveAmountProps.forEach(({ value, currency, hidePaisa, renderedOutput }) => {
    test(`should format positive amount: ${value} for ${currency} currency ${
      hidePaisa ? 'without' : 'with'
    } fractional part`, () => {
      renderApp({
        value,
        currency,
        hidePaisa,
      });
      expect(screen.getByRole('generic', { name: 'amount-info' })).toHaveTextContent(
        renderedOutput,
      );
    });
  });

  positiveAmountProps.forEach(({ value, currency, hidePaisa, renderedOutput }) => {
    test(`should format negative amount: -${value} for ${currency} currency ${
      hidePaisa ? 'without' : 'with'
    } fractional part`, () => {
      renderApp({
        value: `-${value}`,
        currency,
        hidePaisa,
      });
      expect(screen.getByRole('generic', { name: 'amount-info' })).toHaveTextContent(
        `-${renderedOutput}`,
      );
    });
  });
});
