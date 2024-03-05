import React from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

import ListingPage from 'merchant/views/RizeMarketplace/Listing';
import { ListingPageProps } from 'merchant/views/RizeMarketplace/Listing/types';
import { mockData as similarProductsMockData } from 'merchant/views/RizeMarketplace/Marketplace/__tests__/mocks/fixtures';
import { mockFetchProductsAPIResponse } from 'merchant/views/RizeMarketplace/Marketplace/__tests__/mocks/handlers';
import { useRizeMarketplaceStore } from 'merchant/views/RizeMarketplace/common/store';
import { render, screen, userEvent, waitFor, within } from 'test-utils';

import { mockData } from './mocks/fixtures';
import { mockFetchProductBySlugAPIResponse } from './mocks/handlers';

export const observe = jest.fn();
export const unobserve = jest.fn();
export const disconnect = jest.fn();

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

const renderApp = ({
  isMarketplaceOn = true,
  ...props
}: ListingPageProps & { isMarketplaceOn?: boolean }): ReturnType<typeof render> => {
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
      <ListingPage {...props} />
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

const testErrorScreen = async (): Promise<void> => {
  mockFetchProductBySlugAPIResponse({});
  mockFetchProductsAPIResponse({});
  const { history } = renderApp({ slug: 'non-existent-product' });

  expect(await screen.findByText(/could not load product details/i)).toBeInTheDocument();

  const backToMarketplaceButton = screen.getByRole('button', {
    name: /back to marketplace/i,
  });
  expect(backToMarketplaceButton).toBeInTheDocument();

  await userEvent.click(backToMarketplaceButton);
  expect(history.location.pathname).toBe('/rize-marketplace');
};

describe('ListingPage', () => {
  test('should render the product when it exists', async () => {
    mockFetchProductBySlugAPIResponse({});
    mockFetchProductsAPIResponse({});
    const { history } = renderApp({ slug: mockData[0].slug });

    expect(await screen.findByText(mockData[0].data!.name)).toBeInTheDocument();
    expect(
      await screen.findByRole('link', {
        name: /example.com$/i,
      }),
    ).toBeInTheDocument();

    const similarProductCardTile = await screen.findByTestId('product-card-tile');
    expect(similarProductCardTile).toBeInTheDocument();

    const similarProductLink = within(similarProductCardTile).getByRole('link');
    await userEvent.click(similarProductLink);
    expect(history.location.pathname).toBe(`/rize-marketplace/${similarProductsMockData[0].slug}`);
  });

  test('should show error message when product does not exist', testErrorScreen);

  test('should show error message when API throws error', testErrorScreen);

  test('should render product and show error message when similar products could not be loaded', async () => {
    mockFetchProductBySlugAPIResponse({});
    mockFetchProductsAPIResponse({ error: true });
    renderApp({ slug: mockData[0].slug });

    expect(await screen.findByText(mockData[0].data!.name)).toBeInTheDocument();
    expect(await screen.findByText(/could not load similar products/i)).toBeInTheDocument();
  });

  test('should show appropriate message when no similar products were found', async () => {
    mockFetchProductBySlugAPIResponse({});
    mockFetchProductsAPIResponse({});
    renderApp({ slug: mockData[1].slug });

    expect(await screen.findByText(mockData[1].data!.name)).toBeInTheDocument();
    expect(await screen.findByText(/no similar products found/i)).toBeInTheDocument();
  });

  test('should redirect to dashboard when Rize Marketplace experiment is off', async () => {
    const { history } = renderApp({ isMarketplaceOn: false, slug: mockData[0].slug });

    await waitFor(() => {
      expect(history.location.pathname).toEqual('/');
    });
  });
});
