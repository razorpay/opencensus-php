import { render, screen } from 'test-utils';
import V1Transaction from 'merchant/views/Transactions/v1';
import { JPMC_FEATURE_FLAG } from 'merchant/views/Transactions/v1/UploadInvoice/components/constants';

const renderComponent = (tags = []) =>
  render(<V1Transaction />, {
    initialState: {
      session: {
        user: {
          isAuthenticated: true,
          tags,
          isAllowedView: () => true,
        },
      },
    },
  });

describe('Test V1 transaction component', () => {
  test('should not show invoices tab is none of (JPMC, OPGSP) feature flag is enabled', () => {
    renderComponent();

    expect(screen.getByText(/Payments/)).toBeInTheDocument();
    expect(screen.queryByText(/Invoices/)).not.toBeInTheDocument();
  });

  test('should show invoice tab when JPMC feature flag is enabled', () => {
    renderComponent([JPMC_FEATURE_FLAG]);

    expect(screen.getByText(/Invoices/)).toBeInTheDocument();
  });

  test('should show invoice tab when OPGSP feature flag is enabled', () => {
    renderComponent(['opgsp_import_flow']);

    expect(screen.getByText(/Invoices/)).toBeInTheDocument();
  });
});
