import React from 'react';
import { useQuery } from '@tanstack/react-query';

import DigitalBillingInfo from 'merchant/views/StoreSettings/StoreDetails/components/DigitalBillingInfo';
import {
  TERMINAL_MOCK_DATA,
  FETCHED_STORE_INFO,
} from 'merchant/views/StoreSettings/StoreDetails/components/DigitalBillingInfo/__tests__/mocks';
import { screen, render } from 'test-utils';

jest.mock('@tanstack/react-query', () => {
  const original = jest.requireActual('@tanstack/react-query');
  return {
    ...original,
    useQuery: jest.fn().mockReturnValue({
      data: {},
    }),
  };
});

describe('DigitalBillingInfo', () => {
  test("should render 'DigitalBillingInfo' component as expected", () => {
    (useQuery as jest.Mock).mockReturnValueOnce({
      data: {
        storeTerminals: {
          storeTerminals: TERMINAL_MOCK_DATA,
        },
      },
      isFetching: false,
    });

    render(<DigitalBillingInfo fetchedStoreInfo={FETCHED_STORE_INFO} />);
    // Product Specific Info - Section
    expect(screen.getByText('Product Specific Info')).toBeInTheDocument();
    expect(screen.getByText('Selected Brand')).toBeInTheDocument();
    expect(screen.getByText(FETCHED_STORE_INFO.brand.name)).toBeInTheDocument();
    // Billing Terminals - Section
    expect(screen.getByText('Billing Terminals')).toBeInTheDocument();
    // Table header row
    expect(screen.getByText('Terminal No.')).toBeInTheDocument();
    expect(screen.getByText('Name')).toBeInTheDocument();
    expect(screen.getByText('MAC')).toBeInTheDocument();
    expect(screen.getByText('IP Address')).toBeInTheDocument();
    // Table value row
    expect(screen.getByText(TERMINAL_MOCK_DATA.length)).toBeInTheDocument();
    expect(screen.getByText(TERMINAL_MOCK_DATA[0].name)).toBeInTheDocument();
    expect(screen.getByText(TERMINAL_MOCK_DATA[0].terminalInfo.macAddress)).toBeInTheDocument();
    expect(screen.getByText(TERMINAL_MOCK_DATA[0].terminalInfo.ipAddress)).toBeInTheDocument();
  });

  test("should render loader when 'getStoreTerminals' call is loading", () => {
    (useQuery as jest.Mock).mockReturnValueOnce({
      data: { storeTerminals: { storeTerminals: [] } },
      isFetching: true,
    });
    render(<DigitalBillingInfo fetchedStoreInfo={FETCHED_STORE_INFO} />);
    expect(screen.getByRole('progressbar')).toBeInTheDocument();
  });

  test("should not render 'Billing Terminals' section, when selected store is deleted", () => {
    (useQuery as jest.Mock).mockReturnValueOnce({
      data: { storeTerminals: { storeTerminals: [] } },
      isFetching: false,
    });
    render(
      <DigitalBillingInfo
        fetchedStoreInfo={{
          ...FETCHED_STORE_INFO,
          dates: { ...FETCHED_STORE_INFO.dates, deletedAt: '2021-07-28T08:00:00Z' },
        }}
      />,
    );
    expect(screen.queryByRole('progressbar')).not.toBeInTheDocument();
    expect(screen.queryByText('Billing Terminals')).not.toBeInTheDocument();
    // Product Specific Info - section should be displayed
    expect(screen.getByText('Product Specific Info')).toBeInTheDocument();
    expect(screen.getByText('Selected Brand')).toBeInTheDocument();
    expect(screen.getByText('Test Brand')).toBeInTheDocument();
  });

  test('should render placeholder content when no terminal records are available', () => {
    (useQuery as jest.Mock).mockReturnValueOnce({
      data: null,
      isFetching: false,
    });

    render(<DigitalBillingInfo fetchedStoreInfo={FETCHED_STORE_INFO} />);
    // Terminals placeholder content
    expect(screen.getByText('No Billing Terminals added')).toBeInTheDocument();
  });
});
