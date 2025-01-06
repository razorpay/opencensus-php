import React from 'react';
import { useQuery } from '@tanstack/react-query';

import StoreFilter from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/components/StoreFilter';
import {
  STORES_MOCK_RESPONSE,
  STATES_AND_CITIES_MOCK_RESPONSE,
} from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/components/StoreFilter/__tests__/mocks';
import { render, screen } from 'test-utils';

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
window.IntersectionObserver = jest.fn(() => ({
  observe: jest.fn(),
  unobserve: jest.fn(),
  disconnect: jest.fn(),
}));

jest.mock('@tanstack/react-query', () => {
  const original = jest.requireActual('@tanstack/react-query');

  return {
    ...original,
    useQuery: jest.fn().mockReturnValue({
      data: {},
    }),
  };
});

describe('StoreFilter', () => {
  it('should render StoreFilter component', () => {
    const handleSelectStores = jest.fn();

    (useQuery as jest.Mock)
      .mockReturnValueOnce({
        data: STATES_AND_CITIES_MOCK_RESPONSE,
        isFetching: false,
      })
      .mockReturnValueOnce({
        data: STORES_MOCK_RESPONSE,
        isFetching: false,
      });

    render(<StoreFilter onSelectStores={handleSelectStores} selectedStores={{}} />);
    expect(screen.getByText('Stores')).toBeInTheDocument();

    // MultiSelectSlot component
    expect(screen.getByText('State')).toBeInTheDocument();
    expect(screen.getByText('City')).toBeInTheDocument();

    expect(screen.getByPlaceholderText('Search')).toBeInTheDocument();
    expect(screen.getByText('No stores found')).toBeInTheDocument();
  });

  test("should render loader when 'getStores' and 'getStatesAndCities' API are fetching", () => {
    const handleSelectStores = jest.fn();

    (useQuery as jest.Mock)
      .mockReturnValueOnce({
        data: {},
        isFetching: true,
      })
      .mockReturnValueOnce({
        data: {},
        isFetching: true,
      });

    render(<StoreFilter onSelectStores={handleSelectStores} selectedStores={{}} />);
    expect(screen.getAllByRole('progressbar')).toHaveLength(2);
  });
});
