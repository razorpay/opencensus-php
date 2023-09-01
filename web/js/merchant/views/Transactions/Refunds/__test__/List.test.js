import { Provider } from 'react-redux';

import { analyticsTrack } from 'common/utils/analytics';
import * as RzpUtils from 'common/utils/rzp-utils';
import { fetchRefunds } from 'merchant/reducers/collection';
import { storeWithInitialState } from 'merchant/store';
import RefundsListContainer from 'merchant/views/Transactions/Refunds/List';
import { MockListPayload } from 'merchant/views/Transactions/Refunds/__test__/mocks/fixtures';
import { render, fireEvent, waitFor, screen } from 'test-utils';

jest.mock('merchant/reducers/collection', () => ({
  ...jest.requireActual('merchant/reducers/collection'),
  fetchRefunds: jest.fn(),
}));

jest.mock('common/ui/DateRangePicker', () => () => <div>DateRangePicker</div>);

fetchRefunds.mockReturnValue({
  type: 'REFUNDS_FETCH',
  payload: Promise.resolve(MockListPayload),
});

describe('Refunds - List Component', () => {
  afterEach(() => {
    analyticsTrack.mockReset();
  });
  test('should render Refund List component', () => {
    render(
      <Provider
        store={storeWithInitialState({
          session: {
            user: {
              isSingleReconEnabled: true,
              isOptimizerEnabled: true,
            },
          },
        })}
      >
        <RefundsListContainer />
      </Provider>,
    );
    expect(screen.getByTestId('refunds-list')).toBeInTheDocument();
    const submitBtn = screen.getByRole('button', {
      name: 'Search',
    });
    expect(submitBtn).toBeInTheDocument();
    const clearBtn = screen.getByRole('button', {
      name: 'Clear',
    });
    expect(clearBtn).toBeInTheDocument();
  });

  test('should fire refunds search clicked analytics event on submit click', () => {
    render(<RefundsListContainer />);
    const submitBtn = screen.getByRole('button', {
      name: 'Search',
    });
    expect(submitBtn).toBeInTheDocument();
    fireEvent.click(submitBtn);
    expect(analyticsTrack).toHaveBeenCalledWith({
      objectName: 'refunds search',
      actionName: 'clicked',
      screen: 'transactions',
      properties: expect.anything(),
    });
  });

  describe('Refund search result analytics event', () => {
    test('should pass status as success when fetching refunds is success', async () => {
      render(<RefundsListContainer />);
      const submitBtn = screen.getByRole('button', {
        name: 'Search',
      });
      expect(submitBtn).toBeInTheDocument();
      fireEvent.click(submitBtn);

      await waitFor(() => {
        expect(analyticsTrack).toHaveBeenCalledWith({
          objectName: 'refunds search',
          actionName: 'status',
          screen: 'transactions',
          properties: {
            status: 'success',
            location: 'refunds',
            id: undefined,
            refundStatus: undefined,
            emailFilled: false,
            notesFilled: false,
            count: '25',
          },
        });
      });
    });

    test('should pass status as failure when fetching refunds is failure', async () => {
      jest.spyOn(RzpUtils, 'getKeysSeparatedByPipe').mockReturnValue('');
      const mockError = 'fetch error';
      fetchRefunds.mockReturnValue({
        type: 'REFUNDS_FETCH',
        payload: Promise.reject({
          errors: [mockError],
        }),
      });
      render(<RefundsListContainer />);
      const submitBtn = screen.getByRole('button', {
        name: 'Search',
      });
      expect(submitBtn).toBeInTheDocument();
      fireEvent.click(submitBtn);
      await waitFor(() => {
        expect(analyticsTrack).toHaveBeenCalledWith({
          objectName: 'refunds search',
          actionName: 'status',
          screen: 'transactions',
          properties: {
            status: 'failure',
            failureReason: mockError,
            location: 'refunds',
            count: '25',
            from: '',
            to: '',
          },
        });
      });
    });
  });

  test('should call clear seach analytics event on clear CTA', () => {
    render(<RefundsListContainer />);
    const clearBtn = screen.getByRole('button', {
      name: 'Clear',
    });
    fireEvent.click(clearBtn);
    expect(window.rzpAnalytics).toHaveBeenCalled();
  });
});
