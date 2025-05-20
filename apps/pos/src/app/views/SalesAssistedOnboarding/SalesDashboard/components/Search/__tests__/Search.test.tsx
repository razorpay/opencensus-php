import React from 'react';
import {
  render,
  screen,
  server,
  userEvent,
  waitFor,
  act,
} from 'apps/pos/src/services/test/test-utils';
import Search from 'apps/pos/src/app/views/SalesAssistedOnboarding/SalesDashboard/components/Search';
import { useScreen } from 'apps/pos/src/app/utils/hooks/useScreen';
import { getSalesMappedMerchantsHandler } from 'apps/pos/src/app/views/SalesAssistedOnboarding/SalesDashboard/__tests__/mocks/handlers';
interface Range {
  startDate: number;
  endDate: number;
}
interface SearchProps {
  isSearchOpen: boolean;
  setIsSearchOpen: (isSearchOpen: boolean) => void;
  dateRange: Range;
}

jest.mock('apps/pos/src/app/utils/hooks/useScreen', () => ({
  useScreen: jest.fn(),
}));

jest.mock('lodash/debounce', () => {
  const original = jest.requireActual('lodash/debounce');
  return (fn: any, wait: number) => {
    const debounced = original(fn, wait);
    debounced.cancel = jest.fn();
    return debounced;
  };
});

const renderApp = (props: SearchProps) => {
  render(<Search {...props} />);
};

describe('Search', () => {
  const setIsSearchOpen = jest.fn();
  const mockUseScreen = useScreen as jest.Mock;

  beforeAll(() => {
    global.IntersectionObserver = class {
      root: Element | null = null;
      rootMargin: string = '';
      thresholds: ReadonlyArray<number> = [];
      constructor() {}
      observe() {}
      unobserve() {}
      disconnect() {}
      takeRecords() {
        return [];
      }
    };

    mockUseScreen.mockReturnValue({ isMobile: false });
  });

  const defaultProps = {
    isSearchOpen: true,
    setIsSearchOpen,
    dateRange: { startDate: 1622505600000, endDate: 1625097600000 },
  };

  afterEach(() => {
    jest.clearAllMocks();
    mockUseScreen.mockReturnValue({ isMobile: false });
  });

  test('should fetch search results when searchText length is greater than or equal to 3', async () => {
    renderApp(defaultProps);
    const searchInput = screen.getByPlaceholderText('Search');
    server.use(getSalesMappedMerchantsHandler({ type: 'success' }));
    await act(async () => {
      await userEvent.type(searchInput, 'test');
    });
    await waitFor(() => {
      expect(
        screen.queryByText('Search using Merchant ID, Merchant name or Business name.'),
      ).not.toBeInTheDocument();
      expect(
        screen.queryByText('Type a minimum of 3 characters to start searching'),
      ).not.toBeInTheDocument();
    });
  });

  test('should not fetch search results when searchText length is less than 3', async () => {
    renderApp(defaultProps);
    const searchInput = screen.getByPlaceholderText('Search');
    server.use(getSalesMappedMerchantsHandler({ type: 'success' }));
    await act(async () => {
      await userEvent.type(searchInput, 'te');
    });
    await waitFor(() => {
      expect(
        screen.queryByText('Search using Merchant ID, Merchant name or Business name.'),
      ).toBeInTheDocument();
      expect(
        screen.queryByText('Type a minimum of 3 characters to start searching'),
      ).toBeInTheDocument();
    });
  });

  test('should open onboarding details when clicking on a search result and also be able to go back to search', async () => {
    renderApp(defaultProps);
    const searchInput = screen.getByPlaceholderText('Search');
    server.use(getSalesMappedMerchantsHandler({ type: 'success' }));

    await act(async () => {
      await userEvent.type(searchInput, 'test');
    });

    const searchItems = await screen.findAllByTestId('search-item');
    expect(searchItems.length).toBeGreaterThan(0);
    await userEvent.click(searchItems[0]);

    expect(screen.queryByText('Onboarding Details')).toBeInTheDocument();
    expect(screen.queryByText('Pending')).toBeInTheDocument();
    expect(screen.queryByText('Status')).toBeInTheDocument();
    expect(screen.queryByText('Mobile Number')).toBeInTheDocument();
    expect(screen.queryByText('Email')).toBeInTheDocument();
    expect(screen.queryByText('MID')).toBeInTheDocument();
    expect(screen.queryByText('MID created on')).toBeInTheDocument();
    expect(screen.queryByText('Complete now')).toBeInTheDocument();
    expect(screen.queryByText('Back to search')).toBeInTheDocument();

    const backToSearchButton = screen.getByText('Back to search');
    await userEvent.click(backToSearchButton);

    await waitFor(async () => {
      expect(searchInput).toBeInTheDocument();
      expect(screen.queryByText('Onboarding Details')).not.toBeInTheDocument();
    });
  });

  test('should be able to close the search modal', async () => {
    renderApp(defaultProps);
    await userEvent.keyboard('{Escape}');
    expect(setIsSearchOpen).toHaveBeenCalledWith(false);
  });

  test('should render search in mobile view', async () => {
    mockUseScreen.mockReturnValue({ isMobile: true });
    renderApp(defaultProps);

    expect(
      screen.getByText('Type a minimum of 3 characters to start searching'),
    ).toBeInTheDocument();
  });

  test('should open onboarding details when clicking on a search result in mobile view', async () => {
    renderApp(defaultProps);
    mockUseScreen.mockReturnValue({ isMobile: true });
    const searchInput = screen.getByPlaceholderText('Search');
    server.use(getSalesMappedMerchantsHandler({ type: 'success' }));

    await act(async () => {
      await userEvent.type(searchInput, 'CHIZRINZ');
    });

    const searchItems = await screen.findAllByTestId('search-item');
    expect(searchItems.length).toBeGreaterThan(0);
    await userEvent.click(searchItems[0]);

    expect(screen.queryByText('Onboarding Details')).toBeInTheDocument();
    expect(screen.queryByText('Pending')).toBeInTheDocument();
    expect(screen.queryByText('Status')).toBeInTheDocument();
    expect(screen.queryByText('Merchant Name')).toBeInTheDocument();
    expect(screen.queryByText('Mobile Number')).toBeInTheDocument();
    expect(screen.queryByText('Email')).toBeInTheDocument();
    expect(screen.queryByText('MID')).toBeInTheDocument();
    expect(screen.queryByText('MID created on')).toBeInTheDocument();
    expect(screen.queryByText('Complete now')).toBeInTheDocument();
  });
});
