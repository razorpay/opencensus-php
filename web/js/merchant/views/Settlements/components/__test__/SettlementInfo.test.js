import React from 'react';
import Moment from 'moment';
import '@testing-library/jest-dom/extend-expect';
import SettlementInfo from 'merchant/views/Settlements/components/SettlementInfo';
import { render, screen, waitFor, fireEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import * as fetchSettlement from 'merchant/reducers/settlements/details';
import * as modals from 'merchant_common/reducers/modals';
import { titleCase } from 'common/utils/rzp-utils';

const state = {
  session: {
    user: {
      merchant: {
        currency: 'INR',
      },
    },
    org: {},
  },
  home: {
    settlement_amount: { data: {} },
  },
  settlement: {
    config: { data: {} },
    timeline: { data: null },
  },
  navigator: {
    terminalProviders: [],
  },
};

describe('Settlement Info', () => {
  const fetchSettlementTimelineSpy = jest
    .spyOn(fetchSettlement, 'fetchSettlementTimeline')
    .mockReturnValue({
      type: 'SETTLEMENT_TIMELINE_FETCH',
      payload: {
        data: {
          holidays: [1, 2, 3],
          eligible_at: '1 Jan 2022',
          started_at: '1 Apr 2022',
        },
      },
    });
  const modalsSpy = jest.spyOn(modals, 'openModal');

  const App = ({ initialState, ...rest }) => {
    return (
      <Provider store={storeWithInitialState(initialState)}>
        <SettlementInfo {...rest} />
      </Provider>
    );
  };

  beforeEach(() => {
    fetchSettlementTimelineSpy.mockClear();
    modalsSpy.mockClear();
  });

  test('should call fetchtimeline on mount', async () => {
    const data = {
      transaction: {
        id: 'pay_123456',
        created_at: '10 Apr 2022',
      },
    };
    const entityType = 'refund';
    render(<App initialState={state} data={data} entityType={entityType} />);
    await waitFor(() => {
      expect(fetchSettlementTimelineSpy).toHaveBeenCalledTimes(1);
      expect(fetchSettlementTimelineSpy).toHaveBeenCalledWith({
        transaction_id: data.transaction.id?.split('_')[1],
        source_type: entityType,
        created_at: data.transaction.created_at,
      });
    });
  });

  describe('On Hold', () => {
    test('should render on hold transaction', () => {
      const data = {
        transaction: {
          id: 'pay_123456',
          created_at: '10 Apr 2022',
          on_hold: 200,
        },
        on_hold_until: '20 Apr',
      };
      const entityType = 'refund';
      const status = 'on_hold';
      render(
        <App
          initialState={state}
          data={data}
          entityType={entityType}
          showCustomSettlDetails={true}
          adminAsMerchant={true}
        />,
      );
      const onHoldText = screen.getByText(titleCase(status));
      expect(onHoldText).toBeInTheDocument();
      const holdUntilText = screen.getByText(`Hold until ${data.on_hold_until}`);
      expect(holdUntilText).toBeInTheDocument();
    });
    test('should render on hold settlement', () => {
      const initialState = {
        ...state,
        home: {
          settlement_amount: {
            data: { no_settlement: { on_hold: 200 } },
          },
        },
      };
      const data = {
        transaction: {
          id: 'pay_123456',
          created_at: '10 Apr 2022',
        },
      };
      const entityType = 'refund';
      const status = 'under_review';
      render(<App initialState={initialState} data={data} entityType={entityType} />);
      const onHoldText = screen.getByText(titleCase(status));
      expect(onHoldText).toBeInTheDocument();
    });
  });

  describe('View Details', () => {
    test('should call open modal on click view more transaction', async () => {
      const initialState = {
        ...state,
        session: {
          user: {
            hideForNIASupportRole: true,
          },
        },
      };
      const data = {
        transaction: {
          id: 'pay_123456',
          created_at: '10 Apr 2022',
          on_hold: 200,
        },
      };
      const entityType = 'refund';
      render(<App initialState={initialState} data={data} entityType={entityType} />);
      const viewTransactionBtn = screen.getByText('View Details');
      fireEvent.click(viewTransactionBtn);
      await waitFor(() => {
        expect(modalsSpy).toHaveBeenCalledTimes(1);
      });
    });
    test('should call open modal on click view more settlement on hold', async () => {
      const initialState = {
        ...state,
        session: {
          user: {
            hideForNIASupportRole: true,
          },
        },
        home: {
          settlement_amount: {
            data: { no_settlement: { on_hold: 200 } },
          },
        },
      };
      const data = {
        transaction: {
          id: 'pay_123456',
          created_at: '10 Apr 2022',
        },
      };
      const entityType = 'refund';
      render(<App initialState={initialState} data={data} entityType={entityType} />);
      const viewSettlementBtn = screen.getByText('View Details');
      fireEvent.click(viewSettlementBtn);
      await waitFor(() => {
        expect(modalsSpy).toHaveBeenCalledTimes(1);
      });
    });
  });
  describe('Settlements', () => {
    test('should render setllement status when transaction settled', async () => {
      const data = {
        transaction: {
          id: 'pay_123456',
          created_at: '10 Apr 2022',
          settlement: {
            status: 'Payment processed',
          },
        },
      };
      render(
        <App
          initialState={state}
          data={data}
          showCustomSettlDetails={true}
          adminAsMerchant={true}
        />,
      );
      await waitFor(() => {
        const statusLabel = screen.getByText(titleCase(data.transaction.settlement.status));
        expect(statusLabel).toBeInTheDocument();
      });
    });
    test('should render settlement time when transaction settled', () => {
      const data = {
        transaction: {
          id: 'pay_123456',
          created_at: '10 Apr 2022',
          settled_at: '5 Apr 2022',
        },
      };
      render(<App initialState={state} data={data} />);
      const statusLabel = screen.getByText('To be settled on');
      expect(statusLabel).toBeInTheDocument();
      const timeLabel = screen.getByText(
        new Moment(data.transaction.settled_at).format('DD MMM YYYY'),
      );
      expect(timeLabel).toBeInTheDocument();
    });
    test('should render transaction settlement timeline when show timeline enable', () => {
      const data = {
        transaction: {
          id: 'pay_123456',
          created_at: '10 Apr 2022',
          settlement: {
            status: 'Payment processed',
          },
        },
      };
      const initialState = {
        ...state,
        settlement: {
          config: { data: {} },
          timeline: {
            data: {
              holidays: [1, 2, 3],
              eligible_at: '1 Jan 2022',
              started_at: '1 Apr 2022',
              settled_at: '5 Apr 2022',
            },
          },
        },
      };
      render(<App initialState={initialState} data={data} showTimeline={true} />);
      const statusLabel = screen.getByText('Settled on');
      expect(statusLabel).toBeInTheDocument();
      const timeLabel = screen.getByText(
        new Moment(parseInt(initialState.settlement.timeline.data.settled_at, 10)).format(
          'DD MMM YYYY',
        ),
      );
      expect(timeLabel).toBeInTheDocument();
    });
    test('should render settlement timeline and reschedule text based on holidays', () => {
      const data = {
        transaction: {
          id: 'pay_123456',
          created_at: '10 Apr 2022',
          settled_at: '5 Apr 2022',
        },
      };
      const initialState = {
        ...state,
        settlement: {
          config: { data: {} },
          timeline: {
            data: {
              holidays: [1, 2, 3],
              eligible_at: '1 Jan 2022',
              started_at: '1 Apr 2022',
            },
          },
        },
      };
      const { rerender } = render(
        <App initialState={initialState} data={data} showTimeline={true} />,
      );
      const statusLabel = screen.getByText('To be settled on');
      expect(statusLabel).toBeInTheDocument();
      const timeLabel = screen.getByText(
        new Moment(parseInt(initialState.settlement.timeline.data.eligible_at, 10)).format(
          'DD MMM YYYY',
        ),
      );
      expect(timeLabel).toBeInTheDocument();
      let rescheduledText = screen.getByText('Rescheduled due to bank holidays');
      expect(rescheduledText).toBeInTheDocument();

      initialState.settlement.timeline.data.holidays = [];
      rerender(<App initialState={initialState} data={data} showTimeline={true} />);
      rescheduledText = screen.getByText('View settlement timeline');
      expect(rescheduledText).toBeInTheDocument();
    });
  });
  describe('User settlements', () => {
    test('should render settlement details when settled_by included in integratedGateways', () => {
      const initialState = {
        ...state,
        session: {
          user: {
            isSingleReconEnabled: true,
            isOptimizerEnabled: true,
          },
        },
      };
      const data = {
        transaction: {
          id: 'pay_123456',
          created_at: '10 Apr 2022',
        },
        settled_by: 'merchant A',
      };
      render(<App initialState={initialState} data={data} integratedGateways={['merchant A']} />);
      const infoText = screen.getByLabelText('info').textContent;
      expect(infoText).toContain('Settlement details last fetched at');
    });
    test('should render settlement details when settled_by is not included in integratedGateways', () => {
      const initialState = {
        ...state,
        session: {
          user: {
            isSingleReconEnabled: true,
            isOptimizerEnabled: true,
          },
        },
      };
      const data = {
        transaction: {
          id: 'pay_123456',
          created_at: '10 Apr 2022',
        },
        settled_by: 'merchant A',
      };
      render(<App initialState={initialState} data={data} integratedGateways={[]} />);
      const infoText = screen.getByText(
        `Not integrated with ${data.settled_by} to fetch settlement details`,
      );
      expect(infoText).toBeInTheDocument();
    });
  });
});
