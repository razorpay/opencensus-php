import { analyticsTrack } from 'common/utils/analytics';
import {
  refund,
  refundTransaction,
} from 'merchant/views/Transactions/v1/Refunds/__test__/mocks/fixtures';
import RefundDetails from 'merchant/views/Transactions/v1/Refunds/components/RefundDetails';
import { render, screen, fireEvent } from 'test-utils';

jest.mock('merchant/views/Transactions/v1/Payments/components/OptimizerDetails', () => ({
  ...jest.requireActual('merchant/views/Transactions/v1/Payments/components/OptimizerDetails'),
  OptimizerDetails: () => <div data-testid="optimizer-details">optimizer details</div>,
}));

const variantOn = { variables: { result: 'on' } };
const variantOff = { variables: { result: 'off' } };
const defaultAbExperiments = {};
let mockAbExperiments = defaultAbExperiments;

jest.mock('common/splitz', () => ({
  withSplitzService: (Component) => (props) =>
    <Component {...props} splitz={{ abExperiments: mockAbExperiments }} />,
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

const mockViewRefundHistory = jest.fn();

const initProps = {
  refund,
  isLoading: false,
  statusMsg: {},
  viewRefundHistory: mockViewRefundHistory,
};

const renderApp = (props, state) => {
  return render(<RefundDetails {...initProps} {...props} />, {
    initialState: state ?? {
      session: {
        user: {
          isPaymentsExtraRefundDetailsEnabled: true,
          isRefundAllowed: true,
          isOrgAllowedFunctionality: () => true,
        },
      },
    },
  });
};

describe('Refunds - RefundDetails Component', () => {
  beforeEach(() => {
    mockAbExperiments = defaultAbExperiments;
  });

  test('should show spinner when its loading', () => {
    renderApp({
      isLoading: true,
    });
    expect(screen.getByTestId('spinner')).toBeInTheDocument();
  });

  test('should render refund details', () => {
    const refundSpeed = 'instant';
    renderApp({
      refund: {
        ...refund,
        speed_processed: refundSpeed,
      },
    });

    [
      'Refund Id',
      refund.id,
      'Payment',
      refund.payment_id,
      'Status',
      'Amount',
      'Total Fee',
      'RRN/ARN',
      'Refund Speed',
      refundSpeed,
      'Currency',
      refund.currency,
      'Notes',
    ].forEach((fieldLabel) => {
      expect(screen.getAllByText(new RegExp(fieldLabel, 'i'))[0]).toBeInTheDocument();
    });
  });

  test.skip('should call analytic event on component update', () => {
    renderApp();
    expect(analyticsTrack).toHaveBeenCalledWith({
      objectName: 'refund details',
      actionName: 'fetched',
      screen: 'transactions',
      properties: {
        location: 'refunds',
        status: 'success',
      },
    });
  });

  test('should have view history CTA', () => {
    renderApp({
      refund: {
        ...refund,
        speed_processed: undefined,
      },
    });
    const viewHistoryCTA = screen.getByText('View History');
    expect(viewHistoryCTA).toBeInTheDocument();
    fireEvent.click(viewHistoryCTA);
    expect(mockViewRefundHistory).toHaveBeenCalled();
  });

  test('should not render gateway refund details for rzp', () => {
    renderApp();
    expect(screen.queryByTestId('refund-gateway-data')).not.toBeInTheDocument();
  });

  describe('When optimizer experiments are enabled for user', () => {
    const drivingState = {
      session: {
        user: {
          isUxRevampPhase2Enabled: true,
          isSingleReconEnabled: true,
          isOptimizerEnabled: true,
        },
      },
    };

    test('should render optimizer details', () => {
      renderApp({}, drivingState);
      expect(screen.getByTestId('optimizer-details')).toBeInTheDocument();
    });

    test('should render settlement details when refund has transaction', () => {
      renderApp(
        {
          refund: {
            ...refund,
            transaction: refundTransaction,
          },
        },
        drivingState,
      );
      expect(screen.queryByText(/Settlement Details/i)).not.toBeInTheDocument();
    });

    test('should render gateway data if exp is "on" and "gateway_data" is not empty', () => {
      mockAbExperiments = { refund_gateway_data: variantOn };

      const mockProps = {
        refund: {
          ...refund,
          gateway_data: {
            refund_code: 'ERROR_CODE',
            refund_message: 'Sample message',
          },
        },
      };
      renderApp(mockProps, drivingState);

      const gatewayContainer = screen.getByTestId('refund-gateway-data');
      expect(gatewayContainer).toBeInTheDocument();
      expect(gatewayContainer).toHaveTextContent(/Gateway response/i);
      expect(gatewayContainer).toHaveTextContent('Gateway code: ERROR_CODE');
      expect(gatewayContainer).toHaveTextContent('Gateway message: Sample message');
    });

    test('should not render gateway data if exp is "off" and "gateway_data" is not empty', () => {
      mockAbExperiments = { refund_gateway_data: variantOff };

      const mockProps = {
        refund: {
          ...refund,
          gateway_data: {
            refund_code: 'ERROR_CODE',
            refund_message: 'Sample message',
          },
        },
      };
      renderApp(mockProps, drivingState);
      expect(screen.queryByTestId('refund-gateway-data')).not.toBeInTheDocument();
    });

    test('should not render gateway data if exp is "on" but "gateway_data" is empty', () => {
      mockAbExperiments = { refund_gateway_data: variantOn };
      const mockProps = {
        refund: {
          ...refund,
          gateway_data: [],
        },
      };
      renderApp(mockProps, drivingState);
      expect(screen.queryByTestId('refund-gateway-data')).not.toBeInTheDocument();
    });
  });
});
