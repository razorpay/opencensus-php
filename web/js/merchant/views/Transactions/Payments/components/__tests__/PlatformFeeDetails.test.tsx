import React from 'react';
import PlatformFeeDetails from 'merchant/views/Transactions/Payments/components/PlatformFeeDetails';
import { render, screen, userEvent, waitFor } from 'test-utils';
import { paiseToRupees } from 'common/utils/rzp-utils';
import * as analytics from 'common/utils/analytics';

jest.mock('common/ui/LoaderDots', () => () => <>loading</>);

jest.mock('common/ui/Amount', () => ({ value }) => <>{value}</>);

jest.mock('@razorpay/blade/components', () => ({
  __esModule: true,
  Amount: ({ value }) => <>{value}</>,
}));

const state = {
  session: {
    user: {
      id: 'testUserId',
    },
  },
};

describe('PlatformFeeDetails', () => {
  interface PlatformFeeProps {
    payments: {
      fee: number;
      tax: number;
      amount_transferred: number;
    };
    transfer: {
      loading: boolean;
      items: {
        tax: number;
        fees: number;
        amount: number;
        recipient_details: { name: string };
        id: string;
      }[];
    };
  }
  const payment = {
    amount_transferred: 10000,
    tax: 100,
    fee: 100,
  };
  const transfers = {
    items: [
      {
        id: '1',
        amount: 2000,
        createdAt: 612345578,
        tax: 50,
        fees: 50,
        recipient_details: { name: 'merchant' },
      },
      {
        id: '2',
        amount: 200,
        createdAt: 612345578,
        tax: 50,
        fees: 50,
        recipient_details: { name: 'merchant test' },
      },
    ],
    loading: false,
  };
  const { items } = transfers;
  const razorpayFeeAndTax =
    payment.fee + payment.tax + items[0].tax + items[0].fees + items[1].tax + items[1].fees;
  const totalGST = payment.tax + items[0].tax + items[1].tax;

  const analyticsTrackMock = jest.spyOn(analytics, 'analyticsTrack');

  const renderApp = ({ transfer, payments }: PlatformFeeProps) => {
    return render(<PlatformFeeDetails payment={payments} transfers={transfer} />, {
      initialState: state,
    });
  };

  test('should render Razorpay Fee and platform fee details when transfers and payments are present', async () => {
    renderApp({ transfer: { ...transfers }, payments: { ...payment } });
    expect(
      screen.getByText(paiseToRupees(payment.amount_transferred + payment.fee + payment.tax)),
    ).toBeInTheDocument();

    await userEvent.click(
      screen.getByText(`Razorpay Fee & Taxes = ${paiseToRupees(razorpayFeeAndTax)}`),
    );
    expect(screen.getByText(`GST = ${paiseToRupees(totalGST)}`)).toBeInTheDocument();

    await userEvent.click(
      screen.getByText(`Platform Fee = ${paiseToRupees(items[0].amount + items[1].amount)}`),
    );

    expect(
      screen.getByText(`Payment to ${items[0].recipient_details.name} = ${items[0].amount}`),
    ).toBeInTheDocument();
  });

  test('should render loading when transfers are loading', () => {
    renderApp({ transfer: { ...transfers, loading: true }, payments: { ...payment } });
    expect(screen.queryByText('loading')).toBeInTheDocument();
  });

  test('should render no transactions if transfer are empty', async () => {
    renderApp({ transfer: { ...transfers, items: [] }, payments: { ...payment } });
    await userEvent.click(screen.getByText('Platform Fee = 0'));
    expect(screen.getByText('No Transactions Found'));
  });
  test('should capture tab opened event', async () => {
    renderApp({ transfer: { ...transfers }, payments: { ...payment } });
    await waitFor(() => {
      expect(analyticsTrackMock).toHaveBeenCalledWith({
        screen: 'payment details page',
        objectName: 'route partnership payment details',
        actionName: 'tab opened for platform fee',
        properties: {
          mid: 'testUserId',
        },
        toLumberjack: true,
      });
    });
  });
});
