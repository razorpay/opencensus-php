import React from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';

import StoreDetails from 'merchant/views/StoreSettings/StoreDetails';
import { STORE_INFO_MOCK } from 'merchant/views/StoreSettings/StoreDetails/__tests__/mocks';
import { screen, render, userEvent } from 'test-utils';

// Mock 'useQuery' and 'useMutation'
jest.mock('@tanstack/react-query', () => {
  const original = jest.requireActual('@tanstack/react-query');

  return {
    ...original,
    useQuery: jest.fn().mockReturnValue({
      data: {},
    }),
    useMutation: jest.fn(() => ({
      mutate: jest.fn(),
    })),
  };
});

const App = ({ props }) => <StoreDetails {...props} />;

describe('StoreDetails', () => {
  test("should render 'StoreDetails' component as expected", async () => {
    (useQuery as jest.Mock).mockReturnValueOnce({
      data: { storeById: STORE_INFO_MOCK },
      isFetching: false,
    });

    const deleteMutate = jest.fn();
    (useMutation as jest.Mock).mockReturnValue({ mutate: deleteMutate });

    render(<App props={{}} />);

    // Overview
    const overviewTab = screen.getByText('Overview');
    expect(overviewTab).toBeInTheDocument();

    // Delete button
    const deleteButton = screen.getByRole('button', { name: 'Delete' });
    expect(deleteButton).toBeInTheDocument();

    // Edit button
    const editButton = screen.getByRole('button', { name: 'Edit' });
    expect(editButton).toBeInTheDocument();

    // Digital Billing
    const digitalBillingTab = screen.getByText('Digital Billing');
    expect(digitalBillingTab).toBeInTheDocument();
    await userEvent.click(digitalBillingTab);
    expect(screen.getByText('Product Specific Info')).toBeInTheDocument();
  });

  test("should render loader when 'getStoreById' call is fetching", () => {
    (useQuery as jest.Mock).mockReturnValueOnce({
      data: {},
      isFetching: true,
    });

    const deleteMutate = jest.fn();
    (useMutation as jest.Mock).mockReturnValue({ mutate: deleteMutate });

    render(<App props={{}} />);

    // Loader
    expect(screen.getByRole('progressbar')).toBeInTheDocument();
  });

  test("should not render 'Edit' and 'Delete' buttons when fetched store is already deleted", () => {
    (useQuery as jest.Mock).mockReturnValueOnce({
      data: {
        storeById: {
          ...STORE_INFO_MOCK,
          dates: { deletedAt: '2021-09-01T00:00:00Z' },
          storeInfo: { linkedProducts: [] },
        },
      },
      isFetching: false,
    });

    const deleteMutate = jest.fn();
    (useMutation as jest.Mock).mockReturnValue({ mutate: deleteMutate });

    render(<App props={{}} />);

    expect(screen.queryByRole('button', { name: 'Edit' })).not.toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Delete' })).not.toBeInTheDocument();

    // 'Deleted' label should be displayed
    expect(screen.getByText('Deleted')).toBeInTheDocument();

    // 'Digital Billing' tab should not be available when Store's linkedProducts array is not including it
    expect(screen.queryByText('Digital Billing')).not.toBeInTheDocument();
  });
});
