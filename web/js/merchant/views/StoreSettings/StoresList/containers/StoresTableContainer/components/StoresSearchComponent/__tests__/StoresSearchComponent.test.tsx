import React from 'react';

import { STORE_TYPE_MAP } from 'merchant/views/BillMeSettings/common/constants';
import StoresSearchComponent from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/components/StoresSearchComponent';
import {
  LINKED_PRODUCTS_MAP,
  STORES_SEARCH_BY_FIELDS,
} from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/constants';
import { screen, render, userEvent } from 'test-utils';

const setStoresFilterLinkedProducts = jest.fn();
const setStoresFilterStoreType = jest.fn();
const setStoresFilterSearchTerm = jest.fn();
const fetchFilteredStoresData = jest.fn();
const setStoreSearchByColumn = jest.fn();
const setStoresFilterOffset = jest.fn();

const App = ({ props }) => <StoresSearchComponent {...props} />;

describe('StoresSearchComponent', () => {
  test("should render 'StoresSearchComponent' filters as expected", async () => {
    const props = {
      linkedProductsProps: { selectedLinkedProducts: [], setStoresFilterLinkedProducts },
      storeTypeProps: { selectedStoreType: [], setStoresFilterStoreType },
      searchProps: { searchTerm: '', setStoresFilterSearchTerm },
      paginationProps: { offset: 0, setStoresFilterOffset },
      searchColumnProps: {
        selectedSearchByColumn: STORES_SEARCH_BY_FIELDS.STORE_NAME.value,
        setStoreSearchByColumn,
      },
      fetchFilteredStoresData,
    };

    render(<App props={props} />);

    // Linked Razorpay Products
    expect(screen.getByText('Linked Razorpay Products')).toBeInTheDocument();
    const linkedProductsField = screen.getByPlaceholderText('Select Linked Razorpay Products');
    expect(linkedProductsField).toBeInTheDocument();
    await userEvent.click(linkedProductsField);
    expect(screen.getAllByRole('option')).toHaveLength(1);
    await userEvent.click(
      screen.getByRole('option', { name: LINKED_PRODUCTS_MAP.DIGITAL_BILLING.label }),
    );
    expect(setStoresFilterLinkedProducts).toHaveBeenCalledWith([
      LINKED_PRODUCTS_MAP.DIGITAL_BILLING.value,
    ]);

    // Store Type
    expect(screen.getByText('Store Type')).toBeInTheDocument();
    const storeTypeField = screen.getByPlaceholderText('Select Store Type');
    expect(storeTypeField).toBeInTheDocument();
    await userEvent.click(storeTypeField);
    expect(screen.getAllByRole('option')).toHaveLength(4);
    await userEvent.click(screen.getByRole('option', { name: STORE_TYPE_MAP.OFFLINE.label }));
    expect(setStoresFilterStoreType).toHaveBeenCalledWith(STORE_TYPE_MAP.OFFLINE.value);

    // Search By field
    expect(screen.getByText('Search By')).toBeInTheDocument();
    const searchByField = screen.getByPlaceholderText('Select Search By field');
    expect(searchByField).toBeInTheDocument();
    await userEvent.click(searchByField);
    expect(
      screen.getByRole('option', { name: STORES_SEARCH_BY_FIELDS.STORE_NAME.label }),
    ).toBeInTheDocument();
    expect(
      screen.getByRole('option', { name: STORES_SEARCH_BY_FIELDS.STORE_CODE.label }),
    ).toBeInTheDocument();
    await userEvent.click(
      screen.getByRole('option', { name: STORES_SEARCH_BY_FIELDS.STORE_CODE.label }),
    );
    expect(setStoreSearchByColumn).toHaveBeenCalledWith(STORES_SEARCH_BY_FIELDS.STORE_CODE.value);

    // Search field
    const searchField = screen.getByPlaceholderText('Search');
    expect(searchField).toBeInTheDocument();
    await userEvent.type(searchField, '123');
    expect(setStoresFilterSearchTerm).toHaveBeenCalledTimes(3);

    // Search icon
    const searchIcon = screen.getByRole('button');
    expect(searchIcon).toBeInTheDocument();
    await userEvent.click(searchIcon);
    expect(fetchFilteredStoresData).toHaveBeenCalledTimes(1);

    // 'Enter' key from Search field should trigger 'fetchFilteredStoresData'
    await userEvent.type(searchField, '45');
    expect(setStoresFilterSearchTerm).toHaveBeenCalledTimes(5);
    await userEvent.type(searchField, '{enter}');
    expect(fetchFilteredStoresData).toHaveBeenCalledTimes(2);
  });

  test("should render 'StoresSearchComponent' filter fields with pre-filled values from props", async () => {
    const props = {
      linkedProductsProps: {
        selectedLinkedProducts: [LINKED_PRODUCTS_MAP.DIGITAL_BILLING.value],
        setStoresFilterLinkedProducts,
      },
      storeTypeProps: {
        selectedStoreType: STORE_TYPE_MAP.ONLINE.value,
        setStoresFilterStoreType,
      },
      searchColumnProps: {
        selectedSearchByColumn: STORES_SEARCH_BY_FIELDS.STORE_CODE.value,
        setStoreSearchByColumn,
      },
      searchProps: { searchTerm: 'Test Search Term', setStoresFilterSearchTerm },
      paginationProps: { offset: 0, setStoresFilterOffset },
      fetchFilteredStoresData,
    };

    render(<App props={props} />);
    const filterFields = screen.getAllByRole('combobox');
    expect(filterFields).toHaveLength(3);

    // Linked Razorpay Products
    await userEvent.click(filterFields[0]);
    expect(
      screen.getByRole('option', { name: LINKED_PRODUCTS_MAP.DIGITAL_BILLING.label }),
    ).toHaveAttribute('aria-selected', 'true');

    // Store Type
    await userEvent.click(filterFields[1]);
    expect(screen.getByRole('option', { name: STORE_TYPE_MAP.ONLINE.label })).toHaveAttribute(
      'aria-selected',
      'true',
    );

    // Search By field
    await userEvent.click(filterFields[2]);
    expect(
      screen.getByRole('option', { name: STORES_SEARCH_BY_FIELDS.STORE_CODE.label }),
    ).toHaveAttribute('aria-selected', 'true');

    // Search field
    const searchField = screen.getByPlaceholderText('Search');
    expect(searchField).toHaveValue('Test Search Term');
  });
});
