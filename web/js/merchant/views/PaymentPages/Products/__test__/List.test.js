import '@testing-library/jest-dom/extend-expect';
import { render, screen } from 'test-utils';
import {
  List,
  LIST_WITH_UNLIMITED_STOCK_PRODUCT,
  LIST_WITH_OUT_OF_STOCK_PRODUCT,
  LIST_WITH_LIMITED_STOCK_PRODUCT,
} from 'merchant/views/PaymentPages/Products/__test__/mocks/fixtures/List';

describe('List', () => {
  test('should have the expected headers', async () => {
    render(<List />);

    expect(await screen.findByText(/product name/i)).toBeInTheDocument();
    expect(await screen.findByText(/category/i)).toBeInTheDocument();
    expect(await screen.findByText(/price/i)).toBeInTheDocument();
    expect(await screen.findByText(/quantity in stock/i)).toBeInTheDocument();
    expect(await screen.findByText(/status/i)).toBeInTheDocument();
  });

  test('should have available status when having an unlimited stocks product', async () => {
    render(<List products={LIST_WITH_UNLIMITED_STOCK_PRODUCT} />);

    expect(await screen.findByText(/available/i)).toBeInTheDocument();
  });

  test('should have available status when having a limited stocks product', async () => {
    render(<List products={LIST_WITH_LIMITED_STOCK_PRODUCT} />);

    expect(await screen.findByText(/available/i)).toBeInTheDocument();
  });
  test('should have out of stock status when having a product with no stock left', async () => {
    render(<List products={LIST_WITH_OUT_OF_STOCK_PRODUCT} />);

    expect(await screen.findByText(/out of stock/i)).toBeInTheDocument();
  });

  test('should show 0 pieces in quantity in stock when having a product with no stock left', async () => {
    render(<List products={LIST_WITH_OUT_OF_STOCK_PRODUCT} />);

    expect(
      await screen.findByText(new RegExp(`${LIST_WITH_OUT_OF_STOCK_PRODUCT[0].units} pieces`)),
    ).toBeInTheDocument();
  });

  test('should show correct quantity in stock when a product is in stock', async () => {
    render(<List products={LIST_WITH_LIMITED_STOCK_PRODUCT} />);

    expect(await screen.findByText(/10 pieces/i)).toBeInTheDocument();
  });

  test('should show nothing in quantity in stock when a product has unlimited stock', async () => {
    render(<List products={LIST_WITH_UNLIMITED_STOCK_PRODUCT} />);

    expect(await screen.queryByText(/pieces/i)).not.toBeInTheDocument();
  });
});
