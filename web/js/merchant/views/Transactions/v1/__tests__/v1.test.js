import V1Transaction from 'merchant/views/Transactions/v1';
import { JPMC_FEATURE_FLAG } from 'merchant/views/Transactions/v1/UploadInvoice/components/constants';
import { render, screen } from 'test-utils';

const renderComponent = (
  { tags, props, state } = {
    tags: [],
    props: {},
    state: {},
  },
) =>
  render(<V1Transaction {...props} />, {
    initialState: {
      session: {
        user: {
          isAuthenticated: true,
          tags,
          isAllowedView: () => true,
        },
      },
      ...state,
    },
  });

describe('Test V1 transaction component', () => {
  test('should not show invoices tab is none of (JPMC, OPGSP) feature flag is enabled', () => {
    renderComponent();

    expect(screen.getByText(/Payments/)).toBeInTheDocument();
    expect(screen.queryByText(/Invoices/)).not.toBeInTheDocument();
  });

  test('should show invoice tab when JPMC feature flag is enabled', () => {
    renderComponent({
      tags: [JPMC_FEATURE_FLAG],
    });

    expect(screen.getByText(/Invoices/)).toBeInTheDocument();
  });

  test('should show invoice tab when OPGSP feature flag is enabled', () => {
    renderComponent({
      tags: ['opgsp_import_flow'],
    });

    expect(screen.getByText(/Invoices/)).toBeInTheDocument();
  });

  test('Should hide links if jk offline merchant', () => {
    renderComponent({
      state: {
        session: {
          user: {
            isAuthenticated: true,
            isAllowedView: () => true,
            isFeatureEnabled: () => true,
            features: [
              {
                feature: 'omni_enabled',
                value: true,
              },
            ],
          },
          org: {
            isjkOrg: true,
          },
        },
      },
    });
    expect(screen.getByText(/Payments/)).toBeInTheDocument();
    expect(screen.queryByText(/Refunds/)).not.toBeInTheDocument();
  });
});
