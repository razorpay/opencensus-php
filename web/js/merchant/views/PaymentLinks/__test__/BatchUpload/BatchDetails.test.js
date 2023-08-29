import { renderApp } from 'merchant/views/PaymentLinks/__test__/mocks/fixtures/BatchDetails';
import {
  fetchPaymentPageBatch,
  fetchBatchStatsForPLV2,
  fetchBatchPaymentLinks,
} from 'merchant/views/PaymentLinks/__test__/mocks/handlers';
import { waitForLoadingToFinish, screen } from 'test-utils';

describe('PaymentLinksBatchDetailsContainer', () => {
  beforeAll(() => {
    document.cookie = {
      replace: jest.fn(),
    };
  });

  beforeEach(() => {
    window.rzpQ = {
      paymentLinks: () => ({ interaction: jest.fn() }),
    };
  });

  test('should render batch details page"', async () => {
    fetchPaymentPageBatch();
    fetchBatchStatsForPLV2();
    fetchBatchPaymentLinks();
    renderApp({
      props: {
        id: 'valid_batch_id',
        isBatchCancelEnabled: false,
      },
    });
    await waitForLoadingToFinish();
    const labelEl = screen.getByText('Download the report containing all data.');
    const downReportCTA = screen.getByText('Download Report');
    const totalRowsProcessedEl = screen.getByText('Total rows processed');
    const paymentLinksCreatedEl = screen.getByText('Payment links created');
    const paidEl = screen.getByText('Paid');
    const expiredEl = screen.getByText('Expired');
    const statusEl = screen.getByText('Status');
    const createdAtEl = screen.getByText('Created At');
    expect(labelEl).toBeInTheDocument();
    expect(downReportCTA).toBeInTheDocument();
    expect(totalRowsProcessedEl).toBeInTheDocument();
    expect(paymentLinksCreatedEl).toBeInTheDocument();
    expect(paidEl).toBeInTheDocument();
    expect(expiredEl).toBeInTheDocument();
    expect(statusEl).toBeInTheDocument();
    expect(createdAtEl).toBeInTheDocument();
  });
});
