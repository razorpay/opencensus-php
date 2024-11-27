import React from 'react';
import moment from 'moment';
import { render, screen, userEvent, waitFor } from 'test-utils';

import store from 'merchant/store';
import Timeline from 'merchant/views/Settlements/v3/components/Timeline/TimelineRevamp';
import { FailedSettlementInfo } from 'merchant/views/Settlements/v3/utils/settlementInfo';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';

jest.mock('merchant/views/TicketSupport/utils', () => ({
  CreateTicketEmitter: {
    emit: jest.fn(),
  },
}));

const globalStore = store.getState();

const defaultSettlement = {
  amount: 23886,
  created_at: 1678077015,
  entity: 'settlement',
  fees: 0,
  id: 'setl_JCVHSjHRi9QHto',
  status: 'processed',
  tax: 0,
  utr: 'cg2mpl08cfbf3p7nghfg',
};

const getInitialState = ({ settlement = {}, FOH_HOLD = false, SOH_HOLD = false }) => {
  return {
    ...globalStore,
    session: {
      ...globalStore.session,
      user: {
        isOrgRZP: true,
        isOrgCurlec: false,
        merchant: {
          currency: 'INR',
          hold_funds: FOH_HOLD,
        },
      },
    },
    settlement: {
      ...globalStore.settlement,
      settlement: {
        ...globalStore.settlement.settlement,
        ...defaultSettlement,
        ...settlement,
      },
      config: {
        data: {
          config: {
            features: {
              hold: {
                status: SOH_HOLD,
              },
            },
          },
        },
      },
    },
  };
};

const renderApp = ({ initialState }) =>
  render(<Timeline />, {
    initialState,
  });

describe('Settlement details timeline revamp', () => {
  const verifyTimeline = async ({ type, timestamp }) => {
    switch (type) {
      case 'CREATED':
        expect(screen.getByText('Settlement created')).toBeInTheDocument();
        expect(screen.getByText(moment.unix(timestamp).format('llll'))).toBeInTheDocument();
        break;
      case 'YET_TO_BE_PROCESSED':
        expect(screen.getByText('Settlement processed')).toBeInTheDocument();
        expect(screen.getByText('To be processed today')).toBeInTheDocument();
        expect(screen.getByText('Money to be deposited in bank account')).toBeInTheDocument();
        expect(screen.getByText('To be deposited latest by 11:00 pm, today')).toBeInTheDocument();
        break;
      case 'PROCESSED':
        expect(screen.getByText('Settlement processed')).toBeInTheDocument();
        expect(
          screen.getByText(
            'We have successfully processed the settlement. It may take 2-3 hours for the funds to reflect in your bank account. If the money has still not been deposited after this time, please contact your bank using the UTR number (cg2mpl08cfbf3p7nghfg).',
          ),
        ).toBeInTheDocument();
        break;
      case 'PROCESSED_PRE_SLA':
        expect(screen.getByText('Money to be deposited in bank account')).toBeInTheDocument();
        expect(screen.getByText('To be deposited latest by 11:00 pm, today')).toBeInTheDocument();
        break;
      case 'PROCESSED_POST_SLA':
        expect(screen.getByText('Money deposited in bank account')).toBeInTheDocument();
        expect(screen.getByText('UTR number: cg2mpl08cfbf3p7nghfg')).toBeInTheDocument();
        break;
      case 'FAILED':
        expect(screen.getByText('Settlement failed')).toBeInTheDocument();
        expect(
          screen.getByText(FailedSettlementInfo.FAILED.title, {
            exact: false,
          }),
        ).toBeInTheDocument();
        expect(
          screen.getByText(FailedSettlementInfo.FAILED.subtitle, {
            exact: false,
          }),
        ).toBeInTheDocument();
        await userEvent.click(
          screen.getByRole('button', {
            name: 'Contact support',
          }),
        );
        await waitFor(() => {
          expect(CreateTicketEmitter.emit).toHaveBeenCalledWith('create-ticket', 'tickets');
        });
        break;
      case 'FAILED_FOH':
        expect(screen.getByText('Settlement failed')).toBeInTheDocument();
        expect(
          screen.getByText(FailedSettlementInfo.FOH_HOLD.title, {
            exact: false,
          }),
        ).toBeInTheDocument();
        expect(
          screen.getByText(FailedSettlementInfo.FOH_HOLD.subtitle, {
            exact: false,
          }),
        ).toBeInTheDocument();
        expect(
          screen.getByRole('button', {
            name: 'Contact support',
          }),
        ).toBeInTheDocument();
        break;
      case 'FAILED_SOH':
        expect(screen.getByText('Settlement failed')).toBeInTheDocument();
        expect(
          screen.getByText(FailedSettlementInfo.SOH_HOLD.title, {
            exact: false,
          }),
        ).toBeInTheDocument();
        expect(
          screen.getByText(FailedSettlementInfo.SOH_HOLD.subtitle, {
            exact: false,
          }),
        ).toBeInTheDocument();
        expect(
          screen.getByRole('button', {
            name: 'Update Bank account',
          }),
        ).toBeInTheDocument();
        break;
      case 'FAILED_RETRY':
        expect(screen.getByText('Settlement failed')).toBeInTheDocument();
        expect(
          screen.getByText(FailedSettlementInfo.RETRYING.title, {
            exact: false,
          }),
        ).toBeInTheDocument();
        expect(
          screen.getByText(FailedSettlementInfo.RETRYING.subtitle, {
            exact: false,
          }),
        ).toBeInTheDocument();
        break;
      default:
        break;
    }
  };

  test('should render timeline header', () => {
    const initialState = getInitialState({ settlement: {} });
    renderApp({ initialState });
    expect(screen.getByText('Timeline')).toBeInTheDocument();
  });

  test('should render status and created time when status is created', async () => {
    const initialState = getInitialState({
      settlement: {
        status: 'created',
        created_at: 1678077015,
      },
    });
    renderApp({ initialState });
    await verifyTimeline({ type: 'CREATED', timestamp: defaultSettlement.created_at });
    await verifyTimeline({
      type: 'YET_TO_BE_PROCESSED',
      timestamp: defaultSettlement.created_at,
    });
  });

  test('should render status and created time when status is processed and SLA is not breached yet', async () => {
    const createdAt = moment().set({ hour: 22, minute: 58, second: 59 }).unix();
    const initialState = getInitialState({
      settlement: {
        status: 'processed',
        created_at: createdAt,
      },
    });
    renderApp({ initialState });
    await verifyTimeline({ type: 'CREATED', timestamp: createdAt });
    await verifyTimeline({
      type: 'PROCESSED',
      timestamp: defaultSettlement.created_at,
    });
    await verifyTimeline({
      type: 'PROCESSED_PRE_SLA',
      timestamp: defaultSettlement.created_at,
    });
  });

  test('should render status and created time when status is processed and SLA is breached', async () => {
    const initialState = getInitialState({
      settlement: {
        status: 'processed',
        created_at: 1678077015,
      },
    });
    renderApp({ initialState });
    await verifyTimeline({ type: 'CREATED', timestamp: defaultSettlement.created_at });
    await verifyTimeline({
      type: 'PROCESSED',
      timestamp: defaultSettlement.created_at,
    });
    await verifyTimeline({
      type: 'PROCESSED_POST_SLA',
      timestamp: defaultSettlement.created_at,
    });
  });

  test('should render status and created time when status is failed', async () => {
    const initialState = getInitialState({
      settlement: {
        status: 'failed',
        created_at: 1678077015,
      },
    });
    renderApp({ initialState });
    await verifyTimeline({ type: 'CREATED', timestamp: defaultSettlement.created_at });
    await verifyTimeline({
      type: 'FAILED',
      timestamp: defaultSettlement.created_at,
    });
  });

  test('should render status and created time when status is failed due to FOH', async () => {
    const initialState = getInitialState({
      settlement: {
        status: 'failed',
        created_at: 1678077015,
      },
      FOH_HOLD: true,
    });
    renderApp({ initialState });
    await verifyTimeline({ type: 'CREATED', timestamp: defaultSettlement.created_at });
    await verifyTimeline({
      type: 'FAILED_FOH',
      timestamp: defaultSettlement.created_at,
    });
  });

  test('should render status and created time when status is failed due to SOH', async () => {
    const initialState = getInitialState({
      settlement: {
        status: 'failed',
        created_at: 1678077015,
      },
      SOH_HOLD: true,
    });
    renderApp({ initialState });
    await verifyTimeline({ type: 'CREATED', timestamp: defaultSettlement.created_at });
    await verifyTimeline({
      type: 'FAILED_SOH',
      timestamp: defaultSettlement.created_at,
    });
  });

  test('should render status and created time when status is failed within retry period', async () => {
    const createdAt = Date.now() / 1000;
    const initialState = getInitialState({
      settlement: {
        status: 'failed',
        created_at: createdAt,
      },
    });
    renderApp({ initialState });
    await verifyTimeline({ type: 'CREATED', timestamp: createdAt });
    await verifyTimeline({
      type: 'FAILED_RETRY',
      timestamp: defaultSettlement.created_at,
    });
  });
});
