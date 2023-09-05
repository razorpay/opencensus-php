import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import PaymentSplitInItems from 'merchant/views/Transactions/v1/Payments/components/PaymentSplitInItems';
import { render, screen, waitFor, checkIfComponentIsEmpty } from 'test-utils';
import { createMemoryHistory } from 'history';

describe('PaymentSplitInItems', () => {
  const defaultProps = {
    payment: {
      order_id: '456',
    },
  };

  const renderApp = ({ hash = '#paymentpages', props } = {}) => {
    const history = createMemoryHistory();
    history.push({ pathname: '/', hash });
    return render(<PaymentSplitInItems {...defaultProps} {...props} />, {
      history,
    });
  };

  describe('When the provided section is not allowed', () => {
    test('should not render payment split items', () => {
      renderApp({
        hash: '',
      });
      checkIfComponentIsEmpty();
    });
  });

  describe('When the provided section is allowed', () => {
    test('should render payment split items when they are present', async () => {
      renderApp();
      await waitFor(() => {
        expect(screen.getByText('Payment Split')).toBeInTheDocument();
        expect(screen.getByRole('table')).toBeInTheDocument();
      });
      await waitFor(() => {
        expect(screen.getByText('item name 1')).toBeInTheDocument();
        expect(screen.getByText('item name 2')).toBeInTheDocument();
      });
    });

    test('should not render payment split items when they are not present', async () => {
      renderApp({
        props: {
          payment: {
            order_id: '123',
          },
        },
      });
      await waitFor(() => {
        expect(screen.getByText('No Payments Found!')).toBeInTheDocument();
      });
    });
  });
});
