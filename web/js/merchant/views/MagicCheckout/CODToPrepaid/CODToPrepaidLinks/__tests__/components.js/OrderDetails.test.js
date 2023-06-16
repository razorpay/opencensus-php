import { render, screen } from 'test-utils';
import OrderDetails from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/components/OrderDetails';
import {
  receipt,
  date,
  amount,
  rtoRisk,
  discount,
} from 'merchant/views/MagicCheckout/CODOrdersTab/orderInfoDrawer/components/CellItem';
import {
  paymentLinkAction,
  paymentLinkStatus,
} from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/components/CellItem';
import { ORDER_DATA } from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/__tests__/mocks/fixtures';

describe('testing order details component', () => {
  test('component should render properly', () => {
    const mockedOnReviewFn = jest.fn();
    render(
      <OrderDetails
        requiredOrderId="123"
        items={[ORDER_DATA]}
        orderInfoColumns={[
          receipt,
          date,
          amount,
          discount,
          rtoRisk,
          paymentLinkStatus,
          paymentLinkAction(mockedOnReviewFn),
        ]}
        showRecommendation
        showRiskReasons
      />,
    );
    expect(screen.getByText(/Razorpay order id: 123/i)).toBeInTheDocument();
  });

  test('should not show riskReasons, customer details and recommendation if the flag is not passed', () => {
    const mockedOnReviewFn = jest.fn();
    render(
      <OrderDetails
        requiredOrderId="123"
        items={[ORDER_DATA]}
        orderInfoColumns={[receipt, date, amount, rtoRisk, paymentLinkAction(mockedOnReviewFn)]}
        showPaymentStatus
      />,
    );

    expect(screen.queryByText(/cutomer details/i)).not.toBeInTheDocument();
    expect(screen.queryByText(/Risk reasons/i)).not.toBeInTheDocument();
    expect(screen.queryByTestId(/recommendation-data/i)).not.toBeInTheDocument();
  });

  test('should not show expiredOn if pl status is failed', () => {
    const mockedOnReviewFn = jest.fn();
    render(
      <OrderDetails
        requiredOrderId="123"
        items={[ORDER_DATA]}
        orderInfoColumns={[receipt, date, amount, rtoRisk, paymentLinkAction(mockedOnReviewFn)]}
        showPaymentStatus
      />,
    );
    expect(screen.queryByText(/expired on/i)).not.toBeInTheDocument();
  });
});
