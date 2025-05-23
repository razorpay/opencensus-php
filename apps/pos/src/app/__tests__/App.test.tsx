import React from 'react';
import { screen } from '@testing-library/react';
import { render } from 'apps/pos/src/services/test/test-utils';
import { useLocation, useRoutes } from 'react-router-dom';
import { useToast } from '@razorpay/blade/components';
import AssistedOnboarding from '../App';
import { usePullToRefresh } from '../hooks/usePullToRefresh';

// Mock the required dependencies
jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useLocation: jest.fn(),
  useRoutes: jest.fn(),
}));

jest.mock('@razorpay/blade/components', () => ({
  ...jest.requireActual('@razorpay/blade/components'),
  useToast: jest.fn(),
}));

jest.mock('../hooks/usePullToRefresh', () => ({
  usePullToRefresh: jest.fn(),
}));

// Mock window.scrollTo
const scrollToMock = jest.fn();
Object.defineProperty(window, 'scrollTo', {
  writable: true,
  value: scrollToMock,
});

describe('<AssistedOnboarding />', () => {
  const mockToastShow = jest.fn();
  const mockSetupPullToRefresh = jest.fn(() => jest.fn()); // Returns cleanup function

  beforeEach(() => {
    (useLocation as jest.Mock).mockReturnValue({ pathname: '/test' });
    (useRoutes as jest.Mock).mockReturnValue(<div data-testid="mock-routes">Mock Routes</div>);
    (useToast as jest.Mock).mockReturnValue({
      show: mockToastShow,
    });
    (usePullToRefresh as jest.Mock).mockReturnValue({
      setupPullToRefresh: mockSetupPullToRefresh,
    });

    jest.clearAllMocks();
  });

  test('renders correctly with routes', () => {
    render(<AssistedOnboarding />);

    // Check if routes are rendered
    expect(screen.getByTestId('mock-routes')).toBeInTheDocument();

    // Check if the Box containers exist (we can check for their styles)
    const mainContainer = screen.getByTestId('mock-routes').parentElement;
    expect(mainContainer).toHaveStyle({
      display: 'flex',
      flexDirection: 'column',
      backgroundColor: 'surface.background.gray.moderate',
      minHeight: '94vh',
    });
  });

  test('shows a toast notification on initial render', () => {
    render(<AssistedOnboarding />);

    expect(mockToastShow).toHaveBeenCalledWith({
      content: 'Loading...',
      color: 'information',
      autoDismiss: true,
    });
  });

  test('sets up pull-to-refresh functionality', () => {
    render(<AssistedOnboarding />);

    expect(mockSetupPullToRefresh).toHaveBeenCalled();
    // Verify it was called with 2 HTMLDivElements (using any for simplicity)
    expect(mockSetupPullToRefresh.mock.calls[0].length).toBe(2);
  });

  test('scrolls to top when pathname changes', () => {
    render(<AssistedOnboarding />);
    expect(scrollToMock).toHaveBeenCalledWith(0, 0);
  });
});
