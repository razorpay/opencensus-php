import store from 'merchant/store';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import React from 'react';
import { render, screen, server, userEvent, waitFor } from 'test-utils';
import SettlementDetailView from 'merchant/views/Settlements/v3/screens/SettlementDetailView';
import { fetchSettlementDetails } from './mocks/handlers';

jest.mock('merchant/views/Settlements/v3/components/SettlementInfo', () => ({
  __esModule: true,
  default: ({ settlementId }) => <div>Settlement Info:{settlementId}</div>,
}));

jest.mock('merchant/views/Settlements/v3/components/Breakup', () => ({
  __esModule: true,
  default: () => <div>Settlement breakup</div>,
}));

jest.mock('merchant/views/Settlements/v3/components/FAQs', () => ({
  __esModule: true,
  default: () => <div>Frequently asked questions</div>,
}));

jest.mock('merchant/views/Settlements/v3/components/DeductionsEntities/DeductionsEntities', () => ({
  __esModule: true,
  default: () => <div>Deductions entities table</div>,
}));

jest.mock(
  'merchant/views/Settlements/v3/components/GrossSettlementsEntities/GrossSettlementsEntities',
  () => ({
    __esModule: true,
    default: () => <div>Gross settlements entities table</div>,
  }),
);

jest.mock('merchant/views/Settlements/v3/components/Timeline', () => ({
  __esModule: true,
  default: () => <div>settlement timeline</div>,
}));

jest.mock('merchant/views/TicketSupport/utils', () => ({
  CreateTicketEmitter: {
    emit: jest.fn(),
  },
}));

const defaultProps = {
  settlementId: 'JCVHSjHRi9QHto',
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
    server.use(fetchSettlementDetails({ type: 'data' }));
    jest.clearAllMocks();
  });

  test.each([
    { module: 'settlement info', text: `Settlement Info:${defaultProps.settlementId}` },
    { module: 'break up', text: 'Settlement breakup' },
    { module: 'FAQ', text: 'Frequently asked questions' },
    { module: 'timeline', text: 'settlement timeline' },
  ])('should render %s in settlement details view', ({ text }) => {
    renderApp();
    expect(screen.getByText(text)).toBeInTheDocument();
  });

  test('should render contact support banner when settlement failed', async () => {
    renderApp();
    expect(screen.getByText(`Contact support to receive failed settlement`)).toBeInTheDocument();
    expect(
      screen.getByText(
        `Your previous settlement could not be processed as we've encountered a few issues`,
      ),
    ).toBeInTheDocument();

    const contactSupportBtn = screen.getByRole('button', { name: 'Contact support' });
    await userEvent.click(contactSupportBtn);
    expect(CreateTicketEmitter.emit).toHaveBeenCalledWith('create-ticket', 'tickets');
  });

  test.each([
    { type: 'credit', tableText: 'Gross settlements entities table' },
    { type: 'debit', tableText: 'Deductions entities table' },
  ])(
    'should render %s entities table when got items from details api',
    async ({ type, tableText }) => {
      server.use(fetchSettlementDetails({ type }));
      renderApp();
      await waitFor(() => {
        expect(screen.getByText(tableText)).toBeInTheDocument();
      });
    },
  );
});
