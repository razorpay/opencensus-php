import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import { POPULAR_PRODUCTS } from 'merchant/components/HeaderNav/UniversalSearch/constants/SearchProducts';
import ProductListing from 'merchant/components/HeaderNav/UniversalSearch/components/ProductListing';

const defaultProps = {
  setSearch: jest.fn(),
  setFocussed: jest.fn(),
  isMobile: false,
  isDeviceInBreakpoint: true,
  show: true,
  searchResults: {
    isPopular: true,
    products: POPULAR_PRODUCTS,
  },
  searchQuery: '',
};

const validatePopularSearches = () => {
  expect(screen.getByText('Popular searches')).toBeInTheDocument();
  POPULAR_PRODUCTS.forEach(({ item }) => {
    expect(screen.getByText(item.title)).toBeInTheDocument();
  });
};

const renderApp = ({ props }: { props?: Record<string, any> }) =>
  render(<ProductListing {...defaultProps} {...props} />, {
    renderViaRouteGuard: false,
  });

describe('ProductListing', () => {
  test('should render popular products listing', () => {
    renderApp({});
    validatePopularSearches();
  });

  test('should render no result view when no products available', () => {
    renderApp({
      props: {
        searchResults: {
          products: [],
        },
        isDeviceInBreakpoint: false,
      },
    });
    expect(screen.getByText('No search results found')).toBeInTheDocument();
    expect(
      screen.getByText('You can search for payment products, Account & Settings, and more'),
    ).toBeInTheDocument();
  });

  test('should render products listing for mobile view with scroll lock', () => {
    renderApp({
      props: {
        isMobile: true,
      },
    });
    expect(screen.getByTestId('universal-search-results')).toBeInTheDocument();
    expect(document.body.style.overflow).toBe('hidden');
    validatePopularSearches();
  });

  test('should not render popular poducts title when merchant searched query', () => {
    renderApp({
      props: {
        searchResults: {
          isPopular: false,
          products: POPULAR_PRODUCTS,
        },
      },
    });
    expect(screen.queryByText('Popular searches')).not.toBeInTheDocument();
  });

  test('should render nothing when search is not focussed', () => {
    renderApp({
      props: {
        show: false,
      },
    });
    expect(screen.queryByTestId('universal-search-results')).not.toBeInTheDocument();
  });

  test('should redirect on clicking of any product in the listing', async () => {
    const history = {
      push: jest.fn(),
    };
    renderApp({
      props: {
        history,
      },
    });
    const product = screen.getByText(POPULAR_PRODUCTS[0].item.title);
    await userEvent.click(product);
    expect(history.push).toHaveBeenCalledTimes(1);
    expect(history.push).toHaveBeenCalledWith('/payments');
  });
});
