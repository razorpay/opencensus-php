import React from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

import RizeMarketplacePage from 'merchant/views/RizeMarketplace/Marketplace';
import { WEBSITE_LINKS } from 'merchant/views/RizeMarketplace/common/constants';
import { useRizeMarketplaceStore } from 'merchant/views/RizeMarketplace/common/store';
import { render, screen, userEvent, waitFor, within } from 'test-utils';

import { mockData } from './mocks/fixtures';
import { mockFetchProductsAPIResponse } from './mocks/handlers';

const observe = jest.fn();
const unobserve = jest.fn();
const disconnect = jest.fn();

(window as any).IntersectionObserver = jest.fn(() => ({
  observe,
  unobserve,
  disconnect,
}));

// Mocking `scroll` function that is used by Blade's Carousel component
Element.prototype.scroll = jest.fn();

const variantOn = { variables: { result: 'on' } };
const variantOff = { variables: { result: 'off' } };

const defaultAbExperiments = {};
let mockAbExperiments = defaultAbExperiments;
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));

const resetStore = (() => {
  const initial = useRizeMarketplaceStore.getState();
  return () => useRizeMarketplaceStore.setState(initial);
})();

const renderApp = (
  {
    isMarketplaceOn,
  }: {
    isMarketplaceOn?: boolean;
  } = { isMarketplaceOn: true },
): ReturnType<typeof render> => {
  const queryClient = new QueryClient({
    defaultOptions: {
      queries: {
        retry: false,
      },
    },
    logger: {
      log: console.log,
      warn: console.warn,
      error: (): void => {},
    },
  });
  resetStore();

  mockAbExperiments = { rize_marketplace: isMarketplaceOn ? variantOn : variantOff };

  return render(
    <QueryClientProvider client={queryClient}>
      <RizeMarketplacePage />
    </QueryClientProvider>,
    {
      context: {
        user: {
          id: 'a7xSyWa9tt',
        },
      },
    },
  );
};

describe('RizeMarketplacePage', () => {
  test('should render and open products when clicked', async () => {
    mockFetchProductsAPIResponse({});
    const { history } = renderApp();

    // Product card tile
    const [productCardTile] = await screen.findAllByTestId('product-card-tile');
    let deal = within(productCardTile).getByRole('link');
    await userEvent.click(deal);
    expect(history.location.pathname).toEqual(`/rize-marketplace/${mockData[0].slug}`);

    // Product card list
    const [, productCardList] = await screen.findAllByTestId('product-card-list');
    deal = within(productCardList).getByRole('link');
    await userEvent.click(deal);
    expect(history.location.pathname).toEqual(`/rize-marketplace/${mockData[1].slug}`);
  });

  test('should show toast when API call fails', async () => {
    mockFetchProductsAPIResponse({ error: true });
    renderApp();

    await waitFor(() => {
      expect(screen.getByTestId('Notification--error')).toBeInTheDocument();
      expect(screen.getAllByTestId('product-card-tile-skeleton')[0]).toBeInTheDocument();
      expect(screen.queryByTestId('product-card-tile')).not.toBeInTheDocument();
      expect(screen.queryByTestId('product-card-list')).not.toBeInTheDocument();
    });
  });

  test('should search for products', async () => {
    mockFetchProductsAPIResponse({});
    renderApp();

    const searchInput = screen.getByRole('textbox', {
      name: /search marketplace/i,
    });

    await userEvent.type(searchInput, '123{enter}');

    await waitFor(() => {
      expect(screen.getByText('No products found')).toBeInTheDocument();
    });
  });

  test('should filter products', async () => {
    mockFetchProductsAPIResponse({});
    renderApp();

    const filters = screen.getAllByRole('checkbox', {
      hidden: true,
    });
    await userEvent.click(filters[0]);

    await waitFor(() => {
      expect(screen.getAllByTestId('product-card-list').length).toBe(1);

      expect(
        within(screen.getByTestId('product-card-list')).getByText(mockData[0].name),
      ).toBeInTheDocument();
    });
  });

  test('should render rize footer', async () => {
    mockFetchProductsAPIResponse({});
    renderApp();

    const rizeFooter = screen.getByTestId('rize-footer');
    const knowMoreLink = within(rizeFooter).getByRole('link', { name: /know more/i });

    expect(knowMoreLink).toHaveAttribute('href', WEBSITE_LINKS.RIZE_HOMEPAGE);
    expect(knowMoreLink).toHaveAttribute('target', '_blank');

    await userEvent.click(knowMoreLink);
  });

  test('should redirect to dashboard when Rize Marketplace experiment is off', async () => {
    const { history } = renderApp({ isMarketplaceOn: false });

    await waitFor(() => {
      expect(history.location.pathname).toEqual('/');
    });
  });
});
