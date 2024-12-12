import { screen, render } from 'test-utils';
import { LandingContainer } from 'merchant/views/Transactions/v2/Landing/Landing';

const isOrgAllowedFunctionality = jest.fn();

const renderContainer = (tags = []) => {
  return render(<LandingContainer />, {
    initialState: {
      session: {
        user: {
          isAuthenticated: true,
          isAllowedView: () => true,
          isOrgAllowedFunctionality,
          tags,
        },
      },
    },
  });
};

describe('Test V2Transactions Landing Component', () => {
  test('should not show invoices tab if none of (JPMC, OPGSP) feature flag is enabled', () => {
    renderContainer();
    expect(screen.queryByText(/Invoices/)).not.toBeInTheDocument();
  });

  test('should show invoice tab when JPMC feature flag is enabled', () => {
    renderContainer(['enable_jpmc_import_flow']);
    expect(screen.getByText(/Invoices/)).toBeInTheDocument();
  });

  test('should show invoice tab when OPGSP feature flag is enabled', () => {
    renderContainer(['opgsp_import_flow']);
    expect(screen.getByText(/Invoices/)).toBeInTheDocument();
  });
});
