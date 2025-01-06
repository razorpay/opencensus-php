import React from 'react';
import { useQuery } from '@tanstack/react-query';

import BrandsTableContainer from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/BrandsTableContainer';
import { useBrandsTablePayloadStore } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/stores/brandsTablePayloadStore';
import {
  BRAND_BY_ID_RESPONSE,
  BRANDS_RESPONSE,
  BRANDS_FILTER_PAYLOAD,
} from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/__tests__/mocks';
import { screen, render, userEvent, waitFor, act } from 'test-utils';

jest.mock('@tanstack/react-query', () => {
  const original = jest.requireActual('@tanstack/react-query');

  return {
    ...original,
    useQuery: jest.fn().mockReturnValue({
      data: {},
    }),
  };
});

// Mock 'useBrandsTablePayloadStore' Zustand store
jest.mock('../stores/brandsTablePayloadStore', () => ({
  useBrandsTablePayloadStore: jest.fn(),
}));

describe('BrandsTableContainer', () => {
  test("should render 'BrandsTableContainer' component content as expected", async () => {
    const setBrandsFilterSearch = jest.fn();
    (useBrandsTablePayloadStore as unknown as jest.Mock).mockReturnValue({
      brandsFilterPayload: BRANDS_FILTER_PAYLOAD,
      setBrandsFilterSearch,
      setBrandsFilterOffset: jest.fn(),
      setBrandsFilterLimit: jest.fn(),
    });

    const refetch = jest.fn();
    (useQuery as jest.Mock)
      .mockReturnValueOnce({
        refetch,
        data: {
          brands: BRANDS_RESPONSE,
        },
        isFetching: false,
      })
      .mockReturnValue({
        refetch: jest.fn().mockReturnValue({
          data: {
            brandById: BRAND_BY_ID_RESPONSE,
          },
          isFetching: false,
        }),
        data: null,
        isFetching: false,
      });

    render(<BrandsTableContainer />);

    // Search field
    const searchField = screen.getByPlaceholderText('Search by brand name');
    expect(searchField).toBeInTheDocument();
    await act(async () => {
      await userEvent.type(searchField, 'Test');
    });
    expect(setBrandsFilterSearch).toHaveBeenCalledTimes(4);

    // Search icon
    const searchIcon = screen.getAllByRole('button')[0];
    await userEvent.click(searchIcon);

    expect(refetch).toHaveBeenCalledTimes(1);

    // Validate Update modal scenario
    const brandNameLink = screen.getByRole('button', { name: 'Test Brand Name' });
    await act(async () => {
      await userEvent.click(brandNameLink);
    });
    const modalHeader = screen
      .getByText('Brand Details')
      .closest('div[data-blade-component=modal-header]')!;
    const closeModalIcon = modalHeader.querySelector("[aria-label='Close']")!;
    await act(async () => {
      await userEvent.click(closeModalIcon);
    });
    await waitFor(() => {
      expect(screen.queryByText('Brand Details')).not.toBeInTheDocument();
    });
  });

  test("should render create Brand modal and also invoke 'setBrandsFilterOffset' with offset as '0', when current page is not '1' during search", async () => {
    const setBrandsFilterOffset = jest.fn();
    const refetch = jest.fn();
    (useBrandsTablePayloadStore as unknown as jest.Mock).mockReturnValue({
      brandsFilterPayload: { ...BRANDS_FILTER_PAYLOAD, offset: 10 },
      setBrandsFilterOffset,
      setBrandsFilterSearch: jest.fn(),
      setBrandsFilterLimit: jest.fn(),
    });

    (useQuery as jest.Mock).mockReturnValueOnce({
      refetch,
      data: {},
      isFetching: false,
      error: {},
    });

    render(<BrandsTableContainer />);

    // Validate Create modal scenario
    const createNewBrandBtn = screen.getByRole('button', { name: /New Brand/ });
    await act(async () => {
      await userEvent.click(createNewBrandBtn);
    });
    expect(screen.getByText('Add New Brand')).toBeInTheDocument();
    const modalCancelBtn = screen.getByRole('button', { name: 'Cancel' });
    await act(async () => {
      await userEvent.click(modalCancelBtn);
    });
    await waitFor(() => {
      expect(screen.queryByText('Add New Brand')).not.toBeInTheDocument();
    });

    // Search field
    const searchField = screen.getByPlaceholderText('Search by brand name');
    await act(async () => {
      await userEvent.type(searchField, 'Test Search Term');
    });

    // Search icon
    const searchIcon = screen.getAllByRole('button')[0];
    await userEvent.click(searchIcon);
    expect(setBrandsFilterOffset).toHaveBeenLastCalledWith(0);

    // 'Enter' key from Search field should trigger 'refetch'
    await act(async () => {
      await userEvent.type(searchField, 'Test Search Term 1');
      await userEvent.type(searchField, '{enter}');
    });
    expect(setBrandsFilterOffset).toHaveBeenLastCalledWith(0);
  });
});
