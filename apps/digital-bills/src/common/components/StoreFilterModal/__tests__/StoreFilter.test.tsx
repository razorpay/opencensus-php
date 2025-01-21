import React from 'react';
import { useQuery } from '@tanstack/react-query';

import StoreFilter from '@apps/digital-bills/src/common/components/StoreFilterModal/StoreFilter';
import { STATES_AND_CITIES_MOCK_RESPONSE } from '@apps/digital-bills/src/common/components/StoreFilterModal/__tests__/mocks';
import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-expect-error
window.IntersectionObserver = jest.fn(() => ({
  observe: jest.fn(),
  unobserve: jest.fn(),
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
  test('should render StoreFilter component', () => {
    const handleSelectStores = jest.fn();

    (useQuery as jest.Mock).mockReturnValueOnce({
      data: STATES_AND_CITIES_MOCK_RESPONSE,
      isFetching: false,
    });

    const { getByText, getByPlaceholderText, getByRole } = renderWithWrappers(
      <StoreFilter onSelectStores={handleSelectStores} selectedStores={{}} />,
    );
    expect(getByText('Stores')).toBeInTheDocument();

    // MultiSelectSlot component
    expect(getByText('Brands')).toBeInTheDocument();
    expect(getByText('State')).toBeInTheDocument();
    expect(getByText('City')).toBeInTheDocument();
    expect(getByRole('checkbox', { name: 'Karnataka' })).toBeInTheDocument();
    expect(getByRole('checkbox', { name: 'Bangalore' })).toBeInTheDocument();

    // Search input
    expect(getByRole('textbox')).toBeInTheDocument();
    expect(getByPlaceholderText('Search')).toBeInTheDocument();
  });
});
