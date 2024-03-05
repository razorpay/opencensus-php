import React from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

import { mockData } from 'merchant/views/RizeMarketplace/Marketplace/__tests__/mocks/fixtures';
import { mockFetchProductsAPIResponse } from 'merchant/views/RizeMarketplace/Marketplace/__tests__/mocks/handlers';
import RizeMarketplaceAppStoreBanner from 'merchant/views/RizeMarketplace/common/components/RizeMarketplaceAppStoreBanner';
import { render, screen, userEvent, within } from 'test-utils';

// Mocking `scroll` function that is used by Blade's Carousel component
Element.prototype.scroll = jest.fn();

const renderApp = (): ReturnType<typeof render> => {
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

  return render(
    <QueryClientProvider client={queryClient}>
      <RizeMarketplaceAppStoreBanner />
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

export const setupMatchMediaMock = (breakpoint: string): void => {
  Object.defineProperty(window, 'matchMedia', {
    writable: true,
    value: jest.fn().mockImplementation((query: string) => ({
      matches: query.includes(breakpoint),
      media: query,
      addEventListener: jest.fn(),
      removeEventListener: jest.fn(),
    })),
  });
};

const testVisitMarketplaceLinks = async (): Promise<void> => {
  const visitMarketplaceLinks = screen.getByRole('link', {
    name: /visit marketplace/i,
  });

  await userEvent.click(visitMarketplaceLinks);
  expect(visitMarketplaceLinks).toHaveAttribute('href', '/app/rize-marketplace');
  expect(visitMarketplaceLinks).toHaveAttribute('target', '_blank');
};

const testSuccessPath = async (): Promise<void> => {
  mockFetchProductsAPIResponse({});
  renderApp();

  await testVisitMarketplaceLinks();

  const [productCardTile] = await screen.findAllByTestId('product-card-tile');
  const productLinks = within(productCardTile).getAllByRole('link');

  for (const link of productLinks) {
    // eslint-disable-next-line no-await-in-loop
    await userEvent.click(link);
    expect(link).toHaveAttribute('href', `/rize-marketplace/${mockData[0].slug}`);
    expect(link).toHaveAttribute('target', '_blank');
  }
};

describe('RizeMarketplaceAppStoreBanner', () => {
  test('should render with latest products - small screen', testSuccessPath);
  test('should render with latest products - large screen', async () => {
    setupMatchMediaMock('1024px');
    await testSuccessPath();
  });

  test('should render visit marketplace CTAs even when search API throws error', async () => {
    mockFetchProductsAPIResponse({ error: true });
    renderApp();

    await testVisitMarketplaceLinks();
    expect(screen.queryByTestId('product-card-tile')).toBe(null);
  });
});
