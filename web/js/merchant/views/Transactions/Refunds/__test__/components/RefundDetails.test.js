import RefundDetails from 'merchant/views/Transactions/Refunds/components/RefundDetails';
import { render, screen, fireEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { render as reactRender } from '@testing-library/react';
import { Router } from 'react-router-dom';
import { createMemoryHistory } from 'history';
import {
  refund,
  refundTransaction,
} from 'merchant/views/Transactions/Refunds/__test__/mocks/fixtures';
import { analyticsTrack } from 'common/utils/analytics';

jest.mock('merchant/views/Transactions/Payments/components/OptimizerDetails', () => ({
  ...jest.requireActual('merchant/views/Transactions/Payments/components/OptimizerDetails'),
  OptimizerDetails: () => <div data-testid="optimizer-details">optimizer details</div>,
}));

const mockViewRefundHistory = jest.fn();

const initProps = {
  refund,
  isLoading: false,
  statusMsg: {},
  viewRefundHistory: mockViewRefundHistory,
};

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState(state)}>
      <RefundDetails {...initProps} {...props} />
    </Provider>
  );
};

const AppWithRouter = ({ ...props }) => {
  return (
    <Router history={createMemoryHistory({ initialEntries: ['/'] })}>
      <App {...props} />
    </Router>
  );
};

describe('Refunds - RefundDetails Component', () => {
  test('should show spinner when its loading', () => {
    render(<App {...initProps} isLoading={true} />);
    expect(screen.getByTestId('spinner')).toBeInTheDocument();
  });

  test('should render refund details', () => {
    const refundSpeed = 'instant';
    render(
      <App
        refund={{
          ...refund,
          speed_processed: refundSpeed,
        }}
      />,
    );
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

  test('should call analytic event on component update', () => {
    const { rerender } = reactRender(<AppWithRouter />);
    rerender(<AppWithRouter />);
    expect(analyticsTrack).toHaveBeenCalledWith({
      objectName: 'refund details',
      actionName: 'fetched',
      screen: 'transactions',
      properties: {
        location: 'refunds',
        status: 'success',
      },
    });
    expect(analyticsTrack).toHaveBeenCalledTimes(1);
    // should not call analytics track again as refund id is falsy
    rerender(
      <AppWithRouter
        refund={{
          ...refund,
          id: null,
        }}
      />,
    );
    expect(analyticsTrack).toHaveBeenCalledTimes(1);
  });

  test('should have view history CTA', () => {
    render(
      <App
        refund={{
          ...refund,
          speed_processed: undefined,
        }}
      />,
    );
    const viewHistoryCTA = screen.getByText('View History');
    expect(viewHistoryCTA).toBeInTheDocument();
    fireEvent.click(viewHistoryCTA);
    expect(mockViewRefundHistory).toHaveBeenCalled();
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
      render(<App state={drivingState} />);
      expect(screen.getByTestId('optimizer-details')).toBeInTheDocument();
    });

    test('should render settlement details when refund has transaction', () => {
      render(
        <App
          state={drivingState}
          refund={{
            ...refund,
            transaction: refundTransaction,
          }}
        />,
      );
      expect(screen.queryByText(/Settlement Details/i)).not.toBeInTheDocument();
    });
  });
});
