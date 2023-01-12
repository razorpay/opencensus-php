import '@testing-library/jest-dom/extend-expect';
import TransactionLimits from 'merchant/views/AccountAndSettings/PaymentsAndRefundsSettings/Tabs/TransactionLimits';
import * as modals from 'merchant_common/reducers/modals';
import React from 'react';
import { render, screen, userEvent } from 'test-utils';

jest.mock('merchant/views/Account/Profile/components/EditTransactionLimit', () => {
  return {
    __esModule: true,
    default: ({ transactionType, replyHandler }) => (
      <div>
        <span>Edit Transaction Limit</span>
        <span>Transaction Type: {transactionType}</span>
        {transactionType === 'international' && (
          <button onClick={replyHandler}>{transactionType} handler</button>
        )}
      </div>
    ),
  };
});

describe('TransactionLimits', () => {
  const modalsSpy = jest.spyOn(modals, 'openModal');

  const renderApp = ({ props = {} }) => {
    render(<TransactionLimits {...props} />);
  };

  beforeEach(() => {
    modalsSpy.mockClear();
  });

  test.each(['domestic', 'international'])(
    'should render edit transaction limit',
    (transactionType) => {
      renderApp({});
      expect(screen.getAllByText('Edit Transaction Limit').length).toEqual(2);
      expect(screen.getByText(`Transaction Type: ${transactionType}`)).toBeInTheDocument();
    },
  );

  test('should call needs clarification modal in international transactions', async () => {
    renderApp({});
    const replyHandler = screen.getByRole('button', { name: 'international handler' });
    expect(replyHandler).toBeInTheDocument();
    await userEvent.click(replyHandler);
    expect(modalsSpy).toBeCalledTimes(1);
  });
});
