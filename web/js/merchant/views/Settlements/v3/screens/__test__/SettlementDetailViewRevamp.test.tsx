import React from 'react';
import store from 'merchant/store';
import { render, screen, server, waitFor } from 'test-utils';
import SettlementDetailView from 'merchant/views/Settlements/v3/screens/SettlementDetailView/SettlementDetailViewRevamp';
import { fetchSettlementDetails, mockFetchSettlementConfig } from './mocks/handlers';

jest.mock(
  'merchant/views/Settlements/v3/components/SettlementDetailsOverview/SettlementDetailsOverview',
  () => ({
    __esModule: true,
    default: () => <div>Settlement status</div>,
  }),
);

jest.mock('merchant/views/Settlements/v3/components/SettlementInfo/SettlementInfoRevamp', () => ({
  __esModule: true,
  default: () => <div>Settlement Info</div>,
}));

jest.mock('merchant/views/Settlements/v3/components/Breakup/BreakupRevamp', () => ({
  __esModule: true,
  default: () => <div>Settlement breakup</div>,
}));

jest.mock(
  'merchant/views/Settlements/v3/components/DeductionsEntities/DeductionsEntitiesRevamp',
  () => ({
    __esModule: true,
    default: () => <div>Deductions entities table</div>,
  }),
);

jest.mock(
  'merchant/views/Settlements/v3/components/GrossSettlementsEntities/GrossSettlementsEntitiesRevamp',
  () => ({
    __esModule: true,
    default: () => <div>Gross settlements entities table</div>,
  }),
);

jest.mock('merchant/views/Settlements/v3/components/Timeline/TimelineRevamp', () => ({
  __esModule: true,
  default: () => <div>settlement timeline</div>,
}));

const defaultProps = {
  settlementId: 'JCVHSjHRi9QHto',
  status: 'failed',
};

const initialStore = {
  ...store.getState(),
  session: {
    user: {
      activation_status: 'activated',
      merchant: {
        hold_funds: false,
        currency: 'INR',
      },
    },
  },
  settlement: {
    ...store.getState().settlement,
    settlement: {
      amount: 23886,
      created_at: 1678077015,
      entity: 'settlement',
      fees: 0,
      id: 'setl_JCVHSjHRi9QHto',
      status: 'failed',
      tax: 0,
      utr: 'cg2mpl08cfbf3p7nghfg',
    },
  },
};

const renderApp = () =>
  render(<SettlementDetailView {...defaultProps} />, {
    initialState: initialStore,
  });

describe('SettlementDetailsView', () => {
  beforeEach(() => {
    server.use(fetchSettlementDetails({ type: 'data' }), mockFetchSettlementConfig());
    jest.clearAllMocks();
  });

  test.each([
    { module: 'settlement overview', text: 'Settlement status' },
    { module: 'settlement info', text: 'Settlement Info' },
    { module: 'break up', text: 'Settlement breakup' },
    { module: 'timeline', text: 'settlement timeline' },
  ])('should render %s in settlement details view', async ({ text }) => {
    renderApp();
    await waitFor(() => {
      expect(
        screen.getByText(text, {
          exact: false,
        }),
      ).toBeInTheDocument();
    });
  });

  test.each([
    { type: 'credit', tableText: 'Gross settlements entities table' },
    { type: 'debit', tableText: 'Deductions entities table' },
  ])(
    'should render %s entities table when got items from details api',
    async ({ type, tableText }) => {
      server.use(fetchSettlementDetails({ type }), mockFetchSettlementConfig());
      renderApp();
      await waitFor(() => {
        expect(screen.getByText(tableText)).toBeInTheDocument();
      });
    },
  );
});
