import '@testing-library/jest-dom/extend-expect';
import { render, screen, waitForLoadingToFinish, server } from 'test-utils';
import {
  App,
  defaultInitialState,
} from 'merchant/views/PaymentPages/Products/__test__/mocks/fixtures/Products';
import { paymentPagesErrorHandlers } from 'merchant/views/PaymentPages/PaymentPages/__test__/mocks/handlers';

const renderApp = (initialState) => {
  render(<App />, {
    initialState: {
      ...defaultInitialState,
      ...initialState,
    },
  });
};

jest.mock('merchant/views/PaymentPages/Products/List', () => ({
  default: ({ products }) => <span>{products.length}-Products</span>,
  __esModule: true,
}));

describe('Products Container', () => {
  test('should have the necessary CTAs in the container header', () => {
    renderApp();
    expect(screen.getByText('Add a new product')).toBeInTheDocument();
    expect(screen.getByText('Manage Categories')).toBeInTheDocument();
  });

  test('should have the expected filters', async () => {
    renderApp();
    expect(await screen.findByText(/product name/i)).toBeInTheDocument();
    // TODO: Temporarily disabled for initial release
    // expect(await screen.findByText(/category/i)).toBeInTheDocument();
    expect(await screen.findByText(/status/i)).toBeInTheDocument();
  });
  test('should show appropriate message when no products exist', async () => {
    server.use(paymentPagesErrorHandlers.fetchProductsSuccessHandler(true));
    renderApp();

    await waitForLoadingToFinish();

    expect(screen.queryByText('There are no products yet!!')).toBeInTheDocument();
  });

  test('should pass products to List properly', async () => {
    server.use(paymentPagesErrorHandlers.fetchProductsSuccessHandler());
    renderApp();
    await waitForLoadingToFinish();

    expect(screen.getByText('1-Products')).toBeInTheDocument();
  });
});
