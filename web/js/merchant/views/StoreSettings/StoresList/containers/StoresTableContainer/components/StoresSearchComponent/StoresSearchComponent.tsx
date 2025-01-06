import React from 'react';
import {
  Box,
  Dropdown,
  AutoComplete,
  DropdownOverlay,
  ActionListItem,
  ActionList,
  TextInput,
  Button,
  SearchIcon,
} from '@razorpay/blade/components';

import { STORE_TYPE_MAP } from 'merchant/views/BillMeSettings/common/constants';
import {
  LINKED_PRODUCTS_MAP,
  STORES_SEARCH_BY_FIELDS,
} from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/constants';

import type {
  StoreLinkedProductsType,
  StoreSearchColumnType,
  StoreType,
} from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/types';

type StoresSearchComponentProps = {
  linkedProductsProps: {
    selectedLinkedProducts: StoreLinkedProductsType[] | null;
    setStoresFilterLinkedProducts: (linkedProducts: StoreLinkedProductsType[]) => void;
  };
  storeTypeProps: {
    selectedStoreType: StoreType;
    setStoresFilterStoreType: (storeType: StoreType) => void;
  };
  searchColumnProps: {
    selectedSearchByColumn: StoreSearchColumnType;
    setStoreSearchByColumn: (searchTerm: StoreSearchColumnType) => void;
  };
  searchProps: {
    searchTerm: string | undefined;
    setStoresFilterSearchTerm: (searchTerm: string) => void;
  };
  paginationProps: {
    offset: number;
    setStoresFilterOffset: (offset: number) => void;
  };
  fetchFilteredStoresData: () => void;
};

const StoresSearchComponent = ({
  linkedProductsProps: { selectedLinkedProducts, setStoresFilterLinkedProducts },
  storeTypeProps: { selectedStoreType, setStoresFilterStoreType },
  searchColumnProps: { selectedSearchByColumn, setStoreSearchByColumn },
  searchProps: { searchTerm, setStoresFilterSearchTerm },
  paginationProps: { offset, setStoresFilterOffset },
  fetchFilteredStoresData,
}: StoresSearchComponentProps): React.ReactElement => {
  return (
    <Box
      display="flex"
      alignItems="flex-end"
      justifyContent={{ l: 'space-between' }}
      flexWrap="wrap"
      marginTop="spacing.5"
      gap={{ base: 'spacing.3', l: 'spacing.5' }}
    >
      <Box display="flex" gap="spacing.4" width="100%">
        <Box width="50%" maxWidth="50%">
          <Dropdown selectionType="multiple">
            <AutoComplete
              label="Linked Razorpay Products"
              placeholder="Select Linked Razorpay Products"
              name="action"
              value={selectedLinkedProducts || []}
              onChange={({ values }) => {
                // if offset is not 0, reset the offset to fetch the filtered results from the first page
                if (offset > 0) {
                  setStoresFilterOffset(0);
                }
                setStoresFilterLinkedProducts(values as StoreLinkedProductsType[]);
              }}
            />
            <DropdownOverlay>
              <ActionList>
                {Object.values(LINKED_PRODUCTS_MAP).map(({ label, value }) => (
                  <ActionListItem key={value} title={label} value={value} />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        </Box>
        <Box width="50%" maxWidth="50%">
          <Dropdown>
            <AutoComplete
              label="Store Type"
              placeholder="Select Store Type"
              name="action"
              value={selectedStoreType}
              onChange={({ values }) => {
                // if offset is not 0, reset the offset to fetch the filtered results from the first page
                if (offset > 0) {
                  setStoresFilterOffset(0);
                }
                setStoresFilterStoreType(values[0] as StoreType);
              }}
            />
            <DropdownOverlay>
              <ActionList>
                {Object.keys(STORE_TYPE_MAP).map((type) => (
                  <ActionListItem key={type} title={STORE_TYPE_MAP[type]?.label} value={type} />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        </Box>
      </Box>
      <Box display="flex" gap="spacing.4" alignItems="flex-end" width="100%">
        <Box flex="1">
          <Dropdown>
            <AutoComplete
              label="Search By"
              placeholder="Select Search By field"
              name="action"
              value={selectedSearchByColumn}
              onChange={({ values }) => setStoreSearchByColumn(values[0] as StoreSearchColumnType)}
            />
            <DropdownOverlay>
              <ActionList>
                {Object.keys(STORES_SEARCH_BY_FIELDS).map((type) => (
                  <ActionListItem
                    key={type}
                    title={STORES_SEARCH_BY_FIELDS[type]?.label}
                    value={type}
                  />
                ))}
              </ActionList>
            </DropdownOverlay>
          </Dropdown>
        </Box>
        <Box flex="1" display="flex" gap="spacing.3" justifyContent="flex-end">
          <Box flexGrow="1">
            <form
              onSubmit={(e) => {
                e.preventDefault();
                fetchFilteredStoresData();
              }}
            >
              <TextInput
                name="searchfield"
                placeholder="Search"
                accessibilityLabel="Store search field"
                value={searchTerm}
                onChange={({ value }) => setStoresFilterSearchTerm(value || '')}
              />
            </form>
          </Box>
          <Button icon={SearchIcon} onClick={fetchFilteredStoresData} />
        </Box>
      </Box>
    </Box>
  );
};

export default StoresSearchComponent;
