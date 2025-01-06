import React from 'react';
import { useQuery } from '@tanstack/react-query';

import TerminalsTableContainer from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer';
import { useTerminalsTablePayloadStore } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/stores/terminalsTablePayloadStore';
import {
  TERMINALS_LIST_RESPONSE,
  TERMINALS_FILTER_PAYLOAD,
} from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/__tests__/mocks';
import { screen, render, fireEvent } from 'test-utils';

jest.mock('@tanstack/react-query', () => {
  const original = jest.requireActual('@tanstack/react-query');
  return {
    ...original,
    useQuery: jest.fn(),
    useMutation: jest.fn(() => ({
      mutate: jest.fn(),
    })),
  };
});

// Mock 'useTerminalsTablePayloadStore' Zustand store
jest.mock('../stores/terminalsTablePayloadStore', () => ({
  useTerminalsTablePayloadStore: jest.fn(),
}));

const refetch = jest.fn();
const FETCH_TERMINALS_LIST_QUERY = {
  refetch,
  data: TERMINALS_LIST_RESPONSE,
  isFetching: false,
  error: {},
};

describe('TerminalsTableContainer', () => {
  test("should render 'TerminalsTableContainer' component content as expected", () => {
    (useTerminalsTablePayloadStore as unknown as jest.Mock).mockReturnValue({
      terminalsFilterPayload: TERMINALS_FILTER_PAYLOAD,
    });

    (useQuery as jest.Mock).mockReturnValue(FETCH_TERMINALS_LIST_QUERY);

    render(<TerminalsTableContainer />);

    fireEvent.click(screen.getAllByRole('switch')[0]);

    // Search icon
    fireEvent.click(screen.getByTestId('terminals-search-button'));
    expect(refetch).toHaveBeenCalledTimes(1);
  });

  test("should invoke 'setTerminalsFilterOffset' with offset as '0', when current page is not '1' during search", () => {
    const setTerminalsFilterOffset = jest.fn();
    const setTerminalsFilterSearch = jest.fn();
    (useTerminalsTablePayloadStore as unknown as jest.Mock).mockReturnValue({
      terminalsFilterPayload: { ...TERMINALS_FILTER_PAYLOAD, offset: 10 },
      setTerminalsFilterOffset,
      setTerminalsFilterSearch,
    });

    (useQuery as jest.Mock).mockReturnValueOnce(FETCH_TERMINALS_LIST_QUERY);

    render(<TerminalsTableContainer />);

    // Search field
    const searchField = screen.getByPlaceholderText('Search');
    fireEvent.change(searchField, { target: { value: 'Test Search Term' } });
    expect(setTerminalsFilterSearch).toHaveBeenLastCalledWith('Test Search Term');

    // Search icon
    const searchIcon = screen.getByTestId('terminals-search-button');
    fireEvent.click(searchIcon);

    expect(setTerminalsFilterOffset).toHaveBeenLastCalledWith(0);
  });
});
