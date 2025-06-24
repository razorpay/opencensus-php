import React from 'react';
import { screen, fireEvent } from '@testing-library/react';
import GrowthPage from '../GrowthPage';
import { render } from '@apps/shell/src/services/test/render';

// Sample page data for tests - defined at the top level
const mockPageData = {
  title: 'Growth Title',
  description: 'Growth Description',
  imageSrc: 'https://example.com/image.jpg',
  actions: [
    {
      title: 'Primary Action',
      properties: {
        variant: 'primary',
      },
      icon_position: 'right',
      action_params: {
        url: 'https://example.com/primary',
      },
    },
    {
      title: 'Secondary Action',
      properties: {
        variant: 'secondary',
      },
      icon_position: 'left',
      action_params: {
        url: 'https://example.com/secondary',
      },
    },
  ],
};

// Mock the analytics functions - only mock what's needed
jest.mock('@libs/shared-utils', () => {
  const originalModule = jest.requireActual('@libs/shared-utils');
  return {
    ...originalModule,
    analyticsTrack: jest.fn(),
    getCommonAnalyticsProperties: jest.fn().mockReturnValue({}),
  };
});

// Mock the ArrowUpRightIcon component
jest.mock('@razorpay/blade/components', () => {
  const originalModule = jest.requireActual('@razorpay/blade/components');
  return {
    ...originalModule,
    ArrowUpRightIcon: () => <div data-testid="arrow-icon" />,
  };
});

// Mock the useBreakpoint hook
jest.mock('@razorpay/blade/utils', () => {
  const originalModule = jest.requireActual('@razorpay/blade/utils');
  return {
    ...originalModule,
    useBreakpoint: () => ({
      matchedDeviceType: 'desktop',
    }),
  };
});

// Mock the connected navigation store
jest.mock('@federated/apps/shell/connected-navigation/connectedNavigationStore', () => {
  return {
    useConnectedNavigationStore: jest.fn(),
  };
});

describe('GrowthPage', () => {
  // Mock window.open
  const mockOpen = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();

    // Setup window mocks
    Object.defineProperty(window, 'open', {
      writable: true,
      value: mockOpen,
    });

    // Configure the navigation store mock
    const {
      useConnectedNavigationStore,
    } = require('@federated/apps/shell/connected-navigation/connectedNavigationStore');
    useConnectedNavigationStore.mockReturnValue({
      products: {
        selectedProduct: {
          title: 'Test Product',
          selectAction: {
            pageData: mockPageData,
          },
        },
      },
    });
  });

  test('renders with title and description from store', () => {
    render(<GrowthPage />);

    expect(screen.getByText('Growth Title')).toBeTruthy();
    expect(screen.getByText('Growth Description')).toBeTruthy();
  });

  test('renders the image with correct src', () => {
    render(<GrowthPage />);

    const image = screen.getByAltText('Growth Page');
    expect(image).toBeTruthy();
    expect(image.getAttribute('src')).toBe('https://example.com/image.jpg');
  });

  test('renders action buttons from store data', () => {
    render(<GrowthPage />);

    expect(screen.getByText('Primary Action')).toBeTruthy();
    expect(screen.getByText('Secondary Action')).toBeTruthy();
  });

  test('clicking on a button opens URL in a new window', () => {
    render(<GrowthPage />);

    const primaryButton = screen.getByText('Primary Action');
    fireEvent.click(primaryButton);

    // Only check if URL was opened, skip analytics assertions
    expect(mockOpen).toHaveBeenCalledWith('https://example.com/primary', '_blank');
  });
});
