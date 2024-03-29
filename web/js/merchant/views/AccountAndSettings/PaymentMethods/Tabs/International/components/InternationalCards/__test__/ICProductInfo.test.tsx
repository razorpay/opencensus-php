import {
  ICProductStates,
  ProductTypeForAnalytics,
} from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import ICProductInfo, {
  badgeMapping,
  editTransactionLimitLink,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/ICProductInfo';
import * as track from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/utils/track';

const trackIEEventSpy = jest.spyOn(track, 'trackIEEvent');

jest.mock('common/ui/Amount', () => ({
  ...(jest.requireActual('merchant/components/File/Upload') as Record<string, unknown>),
  __esModule: true,
  default: ({ value }) => <div data-testid="amount">Amount: {value}</div>,
}));

jest.mock('@razorpay/blade/components', () => ({
  ...(jest.requireActual('@razorpay/blade/components') as Record<string, unknown>),
  Button: (props) => {
    const { Button } = jest.requireActual('@razorpay/blade/components');
    return (
      <>
        {props.isFullWidth && <p>Full Width: {props.children}</p>}
        <Button {...props} />
      </>
    );
  },
  Badge: (props) => {
    const { Badge } = jest.requireActual('@razorpay/blade/components');
    return (
      <>
        <p>Badge Color: {props.color}</p>
        <Badge {...props} />
      </>
    );
  },
}));

jest.mock('common/ui/Popover', () => ({
  __esModule: true,
  default: ({ children }) => children,
  PopoverBody: ({ children }) => children,
}));

const defaultProps = {
  title: 'test title',
  description: 'test description',
  onRequestAccessClick: jest.fn(),
  status: null,
  product: ProductTypeForAnalytics.PG,
};

const initialState = {
  app: {
    isMobileResolution: false,
  },
};

const renderApp = ({ props = {}, state = {} } = {}) =>
  render(<ICProductInfo {...defaultProps} {...props} />, {
    initialState: {
      ...initialState,
      ...state,
    },
  });

describe('ICProductInfo', () => {
  test('should render title and description', () => {
    renderApp();
    expect(screen.getByText(defaultProps.title)).toBeInTheDocument();
    expect(screen.getByText(defaultProps.description)).toBeInTheDocument();

    expect(screen.queryByRole('button', { name: 'Request to activate' })).not.toBeInTheDocument();
    expect(screen.queryByText('Transaction Size Enabled')).not.toBeInTheDocument();
  });

  test('should render Request to Activate button when status is not activated', async () => {
    renderApp({
      props: {
        status: ICProductStates.NOT_ACTIVATED,
      },
    });
    const requestToActivateBtn = screen.getByRole('button', { name: 'Request to activate' });
    expect(screen.queryByText('Full Width: Request to activate')).not.toBeInTheDocument();
    expect(requestToActivateBtn).toBeInTheDocument();
    await userEvent.click(requestToActivateBtn);
    expect(defaultProps.onRequestAccessClick).toHaveBeenCalledWith(defaultProps.product);
    expect(trackIEEventSpy).toHaveBeenCalledWith({
      objectName: `Request To Activate ${defaultProps.product}`,
      actionName: 'Clicked',
    });
  });

  test('should render FullWidth Request to Activate button when status is not activated on mobile', () => {
    renderApp({
      props: {
        status: ICProductStates.NOT_ACTIVATED,
      },
      state: {
        app: {
          isMobileResolution: true,
        },
      },
    });
    const requestToActivateBtn = screen.getByRole('button', { name: 'Request to activate' });
    expect(requestToActivateBtn).toBeInTheDocument();
    expect(screen.getByText('Full Width: Request to activate')).toBeInTheDocument();
  });

  test.each([
    [badgeMapping[ICProductStates.ACTIVE], ICProductStates.ACTIVE],
    [badgeMapping[ICProductStates.ACTION_REQUIRED], ICProductStates.ACTION_REQUIRED],
    [badgeMapping[ICProductStates.REJECTED], ICProductStates.REJECTED],
    [badgeMapping[ICProductStates.UNDER_REVIEW], ICProductStates.UNDER_REVIEW],
  ])('should show status badge and tooltip - %s when status is %s', (badgeInfo, status) => {
    renderApp({
      props: {
        status,
      },
    });
    expect(screen.getByText(`Badge Color: ${badgeInfo?.color}`)).toBeInTheDocument();
    if (badgeInfo?.tooltip) {
      expect(screen.getByText(badgeInfo.tooltip)).toBeInTheDocument();
    }
  });

  describe('When status is Active', () => {
    test('should show Transaction size, amount, edit transaction link and settlement cycle', () => {
      renderApp({
        props: {
          status: ICProductStates.ACTIVE,
          transactionSize: 100,
          settlementCycle: 7,
        },
      });
      expect(screen.getByText(/Transaction Size Enabled/i)).toBeInTheDocument();
      expect(screen.getByTestId('amount')).toHaveTextContent('Amount: 100');

      const editTransactionLink = screen.getByTestId('edit-transaction-link');
      expect(editTransactionLink).toHaveAttribute('href', editTransactionLimitLink);

      expect(screen.getByText('Settlement Cycle :')).toBeInTheDocument();
      expect(screen.getByText(/T\+7 days/)).toBeInTheDocument();
    });

    test('should call analytics on clicking edit transaction link', async () => {
      renderApp({
        props: {
          status: ICProductStates.ACTIVE,
          transactionSize: 100,
        },
      });
      const editTransactionLink = screen.getByTestId('edit-transaction-link');
      await userEvent.click(editTransactionLink);
      expect(trackIEEventSpy).toHaveBeenLastCalledWith({
        objectName: `Transaction Size Change`,
        actionName: 'Clicked',
        properties: {
          product_limit: defaultProps.product,
        },
      });
    });

    test('should render day in settlement cycle if its one day', () => {
      renderApp({
        props: {
          status: ICProductStates.ACTIVE,
          transactionSize: 100,
          settlementCycle: 1,
        },
      });
      expect(screen.getByText('Settlement Cycle :')).toBeInTheDocument();
      expect(screen.getByText(/T\+1 day/)).toBeInTheDocument();
    });
  });
});
