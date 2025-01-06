import React from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';

import BrandModalComponent from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/components/BrandModalComponent/BrandModalComponent';
import { BRAND_OPERATION_TYPE } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/constants';
import {
  BRAND_MODAL_COMPONENT_MOCK_PROPS,
  BRAND_INFO_MOCK_QUERY_RESPONSE,
} from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/components/BrandModalComponent/__tests__/mocks';
import { screen, render, userEvent, waitFor, act } from 'test-utils';

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

const App = ({ props }) => <BrandModalComponent {...props} />;

describe('BrandModalComponent', () => {
  test("should render 'BrandModalComponent' component as expected, for 'create' operation", async () => {
    const onCloseModal = jest.fn();
    const createMutate = jest.fn();

    (useQuery as jest.Mock).mockImplementation(() => BRAND_INFO_MOCK_QUERY_RESPONSE);

    (useMutation as jest.Mock).mockReturnValue({ mutate: createMutate });

    const props = {
      onCloseModal,
      refetchBrandsList: jest.fn(),
      brandModalInfo: {
        operationType: BRAND_OPERATION_TYPE.CREATE,
        selectedBrandId: null,
      },
    };

    render(<App props={props} />);
    expect(screen.getByText('Add New Brand')).toBeInTheDocument();

    const cancelBtn = screen.getByRole('button', { name: 'Cancel' });
    expect(cancelBtn).toBeInTheDocument();
    await act(async () => {
      await userEvent.click(cancelBtn);
    });
    expect(onCloseModal).toHaveBeenCalledTimes(1);

    const addBtn = screen.getByRole('button', { name: 'Add' });
    expect(addBtn).toBeInTheDocument();

    // 'Add' button should be disabled, when Brand name field is empty
    expect(addBtn).toBeDisabled();

    // Update Brand name field
    await act(async () => {
      await userEvent.type(screen.getByPlaceholderText('Enter Brand Name'), 'Test Brand Name');
    });
    expect(addBtn).toBeEnabled();

    // Create Brand
    await act(async () => {
      await userEvent.click(addBtn);
    });
    expect(createMutate).toHaveBeenCalledTimes(1);
  });

  test("should render 'BrandModalComponent' component as expected, for 'read' operation", async () => {
    const onCloseModal = jest.fn();
    const updateMutate = jest.fn();
    const fetchBrandById = jest.fn();

    (useQuery as jest.Mock).mockReturnValue({
      ...BRAND_INFO_MOCK_QUERY_RESPONSE,
      data: {
        brandById: {
          id: '1234',
          name: 'Test Brand Name',
          description: 'Test Brand Description',
          logo: 'https://assets.billme.co.in/brand/brandlogo-1707219747029-NY%20Cinemas%20Logo.png',
        },
      },
      isSuccess: true,
      refetch: fetchBrandById,
    });

    (useMutation as jest.Mock).mockReturnValue({ mutate: updateMutate });

    render(<App props={{ ...BRAND_MODAL_COMPONENT_MOCK_PROPS, onCloseModal }} />);
    expect(fetchBrandById).toHaveBeenCalledTimes(1);
    expect(useQuery).toHaveBeenLastCalledWith(
      expect.objectContaining({
        queryKey: ['brand_info', '1234'],
        queryFn: expect.any(Function),
        onSuccess: expect.any(Function),
        onError: expect.any(Function),
        enabled: false,
      }),
    );

    // Validate fetched Brand info details
    expect(screen.getByText('Brand Details')).toBeInTheDocument();
    await waitFor(() => {
      expect(screen.getByText('Test Brand Name')).toBeInTheDocument();
    });
    expect(screen.getByText('Test Brand Description')).toBeInTheDocument();

    const editOption = screen.getByRole('button', { name: 'Edit' });
    expect(editOption).toBeInTheDocument();
    expect(editOption).toHaveAttribute('aria-disabled', 'false');

    // 'Cancel' and 'Save' buttons should not be displayed on 'Read' mode
    let cancelBtn = screen.queryByRole('button', { name: 'Cancel' });
    expect(cancelBtn).not.toBeInTheDocument();
    let saveBtn = screen.queryByRole('button', { name: 'Save' });
    expect(saveBtn).not.toBeInTheDocument();

    // 'Update' mode validation
    await act(async () => {
      await userEvent.click(editOption);
    });
    cancelBtn = screen.getByRole('button', { name: 'Cancel' });
    expect(cancelBtn).toBeInTheDocument();
    saveBtn = screen.getByRole('button', { name: 'Save' });
    expect(saveBtn).toBeInTheDocument();
    expect(
      screen.getByText('Recommended Size = 128px x 128px. Max Size = 5MB'),
    ).toBeInTheDocument();

    // 'Edit' option in Header should be disabled in 'Update' mode
    expect(editOption).toBeDisabled();

    await userEvent.click(cancelBtn);
    expect(onCloseModal).toHaveBeenCalledTimes(1);

    // 'Save' button should be disabled, when Brand name field is empty
    const brandNameField = screen.getByPlaceholderText('Enter Brand Name');
    await act(async () => {
      await userEvent.clear(brandNameField);
    });
    expect(saveBtn).toBeDisabled();

    await act(async () => {
      await userEvent.type(brandNameField, 'Test Brand Name');
    });
    expect(saveBtn).toBeEnabled();

    await userEvent.click(saveBtn);
    expect(updateMutate).toHaveBeenCalledTimes(1);
  });

  test("should render 'BrandModalComponent' component as expected, on loading scenario", () => {
    const fetchBrandById = jest.fn();

    (useQuery as jest.Mock).mockReturnValue({
      ...BRAND_INFO_MOCK_QUERY_RESPONSE,
      refetch: fetchBrandById,
      data: {},
      isFetching: true,
    });

    render(<App props={BRAND_MODAL_COMPONENT_MOCK_PROPS} />);
    expect(fetchBrandById).toHaveBeenCalledTimes(1);

    // BrandForm should not be displayed
    expect(screen.queryByText('Image')).not.toBeInTheDocument();

    // Loader should be displayed since 'isFetching' is true from react-query
    expect(screen.getByRole('progressbar')).toBeInTheDocument();
  });
});
