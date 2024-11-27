import React from 'react';
import '@testing-library/jest-dom/extend-expect';

import { titleCase } from 'common/utils/rzp-utils';
import PaymentMethod from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/PaymentMethod';
import {
  cardAppProps,
  upiAppProps,
  turboUPIAppProps,
  netBankingAppProps,
  emiAppProps,
  cardlessEMIAppProps,
  walletAppProps,
  miscAppProps,
} from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/__tests__/mocks/fixtures/PaymentMethod';
import { render, screen, waitFor } from 'test-utils';

describe('Payment Method component', () => {
  const App = ({ props }) => {
    return <PaymentMethod {...props} />;
  };

  describe(`Card method`, () => {
    test('should show Domestic Card payment method', () => {
      render(<App props={cardAppProps} />);
      const cardType = cardAppProps.card.type;
      expect(screen.getByText(`Consumer Domestic ${titleCase(cardType)} card`)).toBeInTheDocument();
    });

    test('should show International Card payment method', () => {
      render(
        <App props={{ ...cardAppProps, card: { ...cardAppProps.card, international: true } }} />,
      );
      const cardType = cardAppProps.card.type;
      expect(
        screen.getByText(`Consumer International ${titleCase(cardType)} card`),
      ).toBeInTheDocument();
    });
  });

  describe(`UPI method`, () => {
    test('should show UPI payment method', async () => {
      render(<App props={upiAppProps} />);

      await waitFor(() => {
        expect(screen.getByText(`UPI`)).toBeInTheDocument();
      });
    });

    test('should show Turbo UPI payment method', () => {
      render(<App props={turboUPIAppProps} />);
      expect(screen.getByTestId(`turbo-upi`)).toBeInTheDocument();
    });
  });

  describe(`Net banking method`, () => {
    test('should show Net banking payment method', () => {
      render(<App props={netBankingAppProps} />);
      expect(screen.getByText(`Net banking`)).toBeInTheDocument();
    });
  });

  describe(`Wallet method`, () => {
    test('should show Wallet payment method', () => {
      render(<App props={walletAppProps} />);
      expect(screen.getByText(`Wallet`)).toBeInTheDocument();
    });
  });

  describe(`EMI method`, () => {
    test('should show EMI payment method', () => {
      render(<App props={emiAppProps} />);
      expect(screen.getByText(/EMI/)).toBeInTheDocument();
    });
  });

  describe(`Cardless EMI method`, () => {
    test('should show Cardless EMI payment method', () => {
      render(<App props={cardlessEMIAppProps} />);
      expect(screen.getByText(`Cardless EMI`)).toBeInTheDocument();
    });
  });

  describe(`Miscellaneous EMI method`, () => {
    test('should show correct/corresponding payment method', () => {
      render(<App props={miscAppProps} />);
      const method = miscAppProps.method;
      expect(screen.getByText(`${titleCase(method)}`)).toBeInTheDocument();
    });
  });
});
