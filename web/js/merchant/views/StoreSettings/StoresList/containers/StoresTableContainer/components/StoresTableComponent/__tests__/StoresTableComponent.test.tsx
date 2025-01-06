import React from 'react';

import { STORE_TYPE_MAP } from 'merchant/views/BillMeSettings/common/constants';
import StoresTableComponent from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/components/StoresTableComponent';
import {
  STORES_DATA,
  COMMON_TABLE_PROPS,
} from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/components/StoresTableComponent/__tests__/mocks';
import { LINKED_PRODUCTS_MAP } from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/constants';
import { screen, render, userEvent } from 'test-utils';

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

const App = ({ props }) => <StoresTableComponent {...props} />;

describe('StoresTableComponent', () => {
  test("should render 'StoresTableComponent' as expected", async () => {
    const changePage = jest.fn();
    const changePageSize = jest.fn();
    const props = {
      tableProps: {
        ...COMMON_TABLE_PROPS,
        changePage,
        changePageSize,
        storesData: STORES_DATA,
      },
      showCreateStoreButton: true,
    };

    render(<App props={props} />);

    // 'New Store' CTA should be displayed when 'showCreateStoreButton' prop is true
    const addNewStoreBtn = screen.getByRole('button', { name: /New Store/ });
    expect(addNewStoreBtn).toBeInTheDocument();

    // Stores table info
    // Table header row
    expect(screen.getByText('Store Name')).toBeInTheDocument();
    expect(screen.getByText('Store Code')).toBeInTheDocument();
    expect(screen.getByText('Store Type')).toBeInTheDocument();
    expect(screen.getByText('Linked Razorpay Products')).toBeInTheDocument();

    // Table value row
    const rowInfo = STORES_DATA[0];
    expect(screen.getByText(rowInfo.name)).toBeInTheDocument();

    // TODO: Store name onClick handler - test scenario would be added after implementation
    expect(screen.getByText(rowInfo.storeInfo.storeCode)).toBeInTheDocument();
    expect(screen.getByText(STORE_TYPE_MAP[rowInfo.storeInfo.storeType].label)).toBeInTheDocument();
    expect(
      screen.getByText(LINKED_PRODUCTS_MAP[rowInfo.storeInfo.linkedProducts[0]].label),
    ).toBeInTheDocument();

    // Stores list table placeholder content should not be displayed when store records are available
    expect(
      screen.queryByText(
        'No store data present. Click on Add New Store to see your stores data here.',
      ),
    ).not.toBeInTheDocument();

    // Page number change
    await userEvent.click(screen.getByText(4));
    expect(changePage).toHaveBeenCalledWith(30);

    // Page size change
    const pageSizePicker = screen.getByRole('combobox');
    await userEvent.click(pageSizePicker);
    await userEvent.click(screen.getByRole('option', { name: '25' }));
    expect(changePageSize).toHaveBeenCalledWith(25);
  });

  test("should render 'StoresTableComponent' as expected when no store records are available", () => {
    const props = {
      tableProps: {
        ...COMMON_TABLE_PROPS,
        storesData: [],
      },
      showCreateStoreButton: false,
    };

    render(<App props={props} />);

    // 'New Store' CTA should not be displayed when 'showCreateStoreButton' prop is false
    expect(screen.queryByRole('button', { name: /New Store/ })).not.toBeInTheDocument();

    // Stores list table placeholder content should be displayed when no store records are available
    expect(
      screen.getByText(
        'No store data present. Click on Add New Store to see your stores data here.',
      ),
    ).toBeInTheDocument();
  });
});
