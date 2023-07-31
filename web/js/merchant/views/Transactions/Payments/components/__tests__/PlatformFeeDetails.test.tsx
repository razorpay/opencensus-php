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
        amount_reversed: number;
        recipient_details: { name: string };
        id: string;
      }[];
    };
  }

  const payment = {
    fee: 207,
    tax: 32,
    amount_transferred: 25000,
  };
  const transfers = {
    items: [
      {
        id: '1',
        createdAt: 612345578,
        tax: 10,
        fees: 60,
        amount: 20000,
        amount_reversed: 0,
        recipient_details: { name: 'merchant' },
      },
      {
        id: '2',
        createdAt: 612345578,
        tax: 2,
        fees: 15,
        amount: 5000,
        amount_reversed: 5000,
        recipient_details: { name: 'merchant test' },
      },
    ],
    loading: false,
  };
  const { items } = transfers;
  const totalFeeAmount =
    payment.fee +
    items[0].fees +
    items[0].amount -
    items[0].amount_reversed +
    items[1].fees +
    items[1].amount -
    items[1].amount_reversed;

  const platformFee =
    items[0].amount +
    items[0].fees -
    items[0].amount_reversed +
    items[1].amount +
    items[1].fees -
    items[1].amount_reversed;

  const analyticsTrackMock = jest.spyOn(analytics, 'analyticsTrack');

  const renderApp = ({ transfer, payments }: PlatformFeeProps) => {
    return render(<PlatformFeeDetails payment={payments} transfers={transfer} />, {
      initialState: state,
    });
  };

  test('should render Razorpay Fee and platform fee details when transfers and payments are present', async () => {
    renderApp({ transfer: { ...transfers }, payments: { ...payment } });
    expect(screen.getByText(paiseToRupees(totalFeeAmount))).toBeInTheDocument();

    await userEvent.click(screen.getByText(`Razorpay Fee & Taxes = ${paiseToRupees(payment.fee)}`));
    expect(screen.getByText(`GST = ${paiseToRupees(payment.tax)}`)).toBeInTheDocument();

    await userEvent.click(screen.getByText(`Platform Fee = ${paiseToRupees(platformFee)}`));

    expect(
      screen.getByText(
        `Payment to ${items[0].recipient_details.name} = ${
          items[0].amount - items[0].amount_reversed
        }`,
      ),
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
