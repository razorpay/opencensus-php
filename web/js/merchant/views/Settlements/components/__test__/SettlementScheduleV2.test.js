import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import SettlementScheduleV2 from 'merchant/views/Settlements/components/SettlementScheduleV2';
import { render, screen, waitFor } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import userEvent from '@testing-library/user-event';
import * as modals from 'merchant_common/reducers/modals';

const state = {
  session: {
    user: {
      isMarketplaceEnabled: true,
    },
    org: {},
  },
  settlement: {
    config: {
      data: {
        config: {
          schedules: {
            payment: {
              'domestic:default': 'T+2 1PM',
              'international:default': 'T+7 9AM',
            },
            refund: { default: 'Instant' },
            reversal: { default: 'Instant' },
          },
        },
      },
    },
  },
};

describe('SettlementScheduleV2', () => {
  const modalsSpy = jest.spyOn(modals, 'openModal');

  const App = ({ initialState = state, ...rest }) => {
    return (
      <Provider store={storeWithInitialState(initialState)}>
        <SettlementScheduleV2 {...rest} />
      </Provider>
    );
  };

  beforeEach(() => {
    modalsSpy.mockClear();
  });

  test('should render bank holidays button and settlement guide link', () => {
    render(<App />);
    expect(screen.getByText('List of Bank Holidays')).toBeInTheDocument();
    expect(screen.getByText('Settlement Guide')).toBeInTheDocument();
  });

  test('should call open modal on click of view holidays', async () => {
    render(<App />);
    userEvent.click(screen.getByText('List of Bank Holidays'));
    await waitFor(() => {
      expect(modalsSpy).toHaveBeenCalledTimes(1);
    });
  });

  describe('Settlement schedule', () => {
    test('should render payment settlement cycle', () => {
      render(<App />);
      expect(screen.getByText(/Payments default settlement cycle/)).toBeInTheDocument();
    });

    test('should render schedule info', () => {
      render(<App />);
      expect(screen.getByText('T is the date of payment capture')).toBeInTheDocument();
    });

    test('should render other Settlement cycle', () => {
      render(<App />);
      expect(screen.getByText('Other Settlement cycle')).toBeInTheDocument();
    });

    test('should render refunds entity schedule', () => {
      const entityType = 'refunds';
      render(<App />);
      expect(screen.getByText(entityType)).toBeInTheDocument();
      expect(
        screen.getAllByText(state.settlement.config.data.config.schedules.refund.default)[0],
      ).toBeInTheDocument();
    });

    test('should render reversal entity schedule', () => {
      const initialState = {
        ...state,
        settlement: {
          config: {
            data: {
              config: {
                schedules: {
                  payment: {
                    'domestic:default': 'T+2 1PM',
                    'international:default': 'T+7 9AM',
                  },
                  reversal: { default: 'Reversal payment schedule' },
                },
              },
            },
          },
        },
      };
      const entityType = 'reversals';
      const info =
        'The fund transfer happens internally as per the given schedule, the credit to linked accounts will happen as per the settlement schedule of the linked accounts.';
      render(<App initialState={initialState} />);
      expect(screen.getByText(entityType)).toBeInTheDocument();
      expect(
        screen.getByText(initialState.settlement.config.data.config.schedules.reversal.default),
      ).toBeInTheDocument();
      expect(screen.getByText(info)).toBeInTheDocument();
      expect(screen.queryByText('refunds')).not.toBeInTheDocument();
    });

    test('should render transfer entity schedule', () => {
      const initialState = {
        ...state,
        settlement: {
          config: {
            data: {
              config: {
                schedules: {
                  payment: {
                    'domestic:default': 'T+2 1PM',
                    'international:default': 'T+7 9AM',
                  },
                  transfer: {
                    default: 'Transfers payment schedule',
                  },
                },
              },
            },
          },
        },
      };
      const entityType = 'transfers';
      const info =
        'The fund transfer happens internally as per the given schedule, the credit to linked accounts will happen as per the settlement schedule of the linked accounts.';
      render(<App initialState={initialState} />);
      expect(screen.getByText(entityType)).toBeInTheDocument();
      expect(
        screen.getByText(initialState.settlement.config.data.config.schedules.transfer.default),
      ).toBeInTheDocument();
      expect(screen.getByText(info)).toBeInTheDocument();
      expect(screen.queryByText('refunds')).not.toBeInTheDocument();
    });

    test('should render schedule info communication', () => {
      render(<App />);
      expect(screen.getByText('T is the date of initiation')).toBeInTheDocument();
    });
  });

  describe('Settlement examples', () => {
    test('should render bank holiday note', () => {
      render(<App />);
      expect(screen.getByText('Bank holidays')).toBeInTheDocument();
      expect(screen.getByText('View Example')).toBeInTheDocument();
    });

    test('should render settlement holiday example and example image on click of view button', async () => {
      render(<App />);
      userEvent.click(screen.getByText('View Example'));
      await waitFor(() => {
        expect(screen.getByText('Hide Example')).toBeInTheDocument();
        expect(
          screen.getByText('Assume settlement schedule for a payment is T+3'),
        ).toBeInTheDocument();
        expect(screen.getByRole('img')).toHaveAttribute(
          'src',
          'https://cdn.razorpay.com/static/assets/settlements/settlement-example-new.svg',
        );
        expect(screen.getByRole('img')).toHaveAttribute('alt', 'settlement holiday example');
      });
    });
  });
});
