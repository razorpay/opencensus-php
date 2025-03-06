import React, { useMemo, useState, useEffect } from 'react';
import {
  Box,
  Button,
  Divider,
  DatePicker,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
  AutoComplete,
  TextInput,
  Text,
  DropdownFooter,
  Link,
  ArrowRightIcon,
  ActionListSection,
} from '@razorpay/blade/components';

import {
  zIndicesMap,
  BillSearchByOptions,
  BillStatusTypes,
  TransactionTypes,
  MAX_DATE_FOR_DATE_PICKER,
} from '@apps/digital-bills/src/utils/constants';
import { useBillsTablePayloadStore } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/stores/billsTablePayloadStore';
import {
  getDateRangePresets,
  getSelectedDatesFromPicker,
  getUpdatedDateRange,
} from '@apps/digital-bills/src/utils/helpers/getDateRangePresets';
import useSearchByOptions from '@apps/digital-bills/src/common/hooks/useSearchByOptions';
import useMinMaxAmount from '@apps/digital-bills/src/common/hooks/useMinMaxAmount';
import { DEFAULT_STORE_GROUP } from '@apps/digital-bills/src/common/components/StoreFilterModal/constants';
import type {
  BillSearchInputs,
  BillStatus,
  BillUserSearchInput,
  StoreGroup,
  StoreGroupsDataResponse,
  StoresDataResponse,
  TransactionType,
} from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

type BillsSearchComponentProps = {
  resetBillsFilter: () => void;
  fetchFilteredBillsData: () => void;
  storeGroupsResponse: StoreGroupsDataResponse | undefined;
  storesResponse: StoresDataResponse | undefined;
};

const BillsSearchComponent = ({
  resetBillsFilter,
  fetchFilteredBillsData,
  storesResponse,
  storeGroupsResponse,
}: BillsSearchComponentProps) => {
  const [selectedSearchOption, setSelectedSearchOption] = useState('');
  const {
    billsFilterPayload,
    setStoreFilterModalOpen,
    setBillsStoreIds,
    storeGroupInputValue,
    setStoreGroupInputValue,
    setBillsFilterDateRange,
    setBillsFilterStatus,
    setBillsFilterTransaction,
    setBillsFilterMinAmount,
    setBillsFilterMaxAmount,
    setBillsFilterSearchByUser,
    setBillsFilterSearch,
  } = useBillsTablePayloadStore();

  const {
    fromDate,
    toDate,
    status,
    transactionType,
    minAmount,
    maxAmount,
    invoiceNumber,
    storeCode,
    user,
  } = billsFilterPayload;
  const searchInputs = {
    invoiceNumber,
    storeCode,
    email: user.email,
    contact: user.contact,
  };

  const openModal = () => {
    setStoreFilterModalOpen(true);
  };

  const {
    minAmountValidationState,
    maxAmountValidationState,
    minAmountErrorMsg,
    maxAmountErrorMsg,
    amountChangeHandler,
  } = useMinMaxAmount(minAmount, maxAmount, setBillsFilterMinAmount, setBillsFilterMaxAmount);

  const {
    searchValidationState,
    searchErrorMsg,
    searchByOptionHandler,
    searchHandler,
    getSearchInputValue,
  } = useSearchByOptions<BillSearchInputs>(
    searchInputs,
    selectedSearchOption,
    setSelectedSearchOption,
    setBillsFilterSearch,
    setBillsFilterSearchByUser,
  );

  const [selectedGroup, setSelectedGroup] = useState(DEFAULT_STORE_GROUP?.id);
  const [storeGroupOptions, setStoreGroupOptions] = useState<StoreGroup[]>([DEFAULT_STORE_GROUP]);
  const storeGroups = useMemo(
    () => storeGroupsResponse?.storeGroups?.storeGroups ?? [],
    [storeGroupsResponse],
  );
  const storesTotalCount = useMemo(() => storesResponse?.stores?.total ?? 0, [storesResponse]);

  useEffect(() => {
    setStoreGroupOptions([
      { ...DEFAULT_STORE_GROUP, storesCount: storesTotalCount },
      ...storeGroups.map((group) => ({ ...group, storesCount: group.stores.length })),
    ]);
    if (storeGroupInputValue.includes('All Stores')) {
      setStoreGroupInputValue(`All Stores (${storesTotalCount || 0})`);
    }
  }, [storeGroups, storesTotalCount]);

  const handleDatesChange = (range) => {
    const { fromDate, toDate } = getSelectedDatesFromPicker(range);
    setBillsFilterDateRange(fromDate, toDate);
  };

  const handleSearchByOptionChange = ({ values }) => {
    if (selectedSearchOption) {
      if (
        selectedSearchOption === BillSearchByOptions?.Email?.Value ||
        selectedSearchOption === BillSearchByOptions?.PhoneNumber?.Value
      ) {
        setBillsFilterSearchByUser(selectedSearchOption as keyof BillUserSearchInput, '');
      } else {
        setBillsFilterSearch(selectedSearchOption, '');
      }
    }
    searchByOptionHandler(values[0]);
  };

  return (
    <Box paddingTop="spacing.5">
      <Box>
        <Box
          display="flex"
          flexDirection={{ base: 'column', xl: 'row' }}
          height="100%"
          marginBottom="spacing.5"
          gap="spacing.5"
        >
          <Box display="flex" flex={4} gap="spacing.5" flexDirection={{ base: 'column', l: 'row' }}>
            <Box flex={1.3}>
              <Dropdown selectionType="multiple">
                <AutoComplete
                  label="Status"
                  placeholder="Select Bill Status"
                  name="action"
                  value={status}
                  onChange={({ values }) => setBillsFilterStatus(values as BillStatus[])}
                />
                <DropdownOverlay>
                  <ActionList>
                    {Object.values(BillStatusTypes).map(({ Name: name, Value: value }) => (
                      <ActionListItem key={value} title={name} value={value} />
                    ))}
                  </ActionList>
                </DropdownOverlay>
              </Dropdown>
            </Box>
            <Box flex={2}>
              <DatePicker
                allowSingleDateInRange
                /* @ts-expect-error:next-line */
                label={{
                  start: 'Duration',
                }}
                /* @ts-expect-error:next-line */
                selectionType="range"
                value={getUpdatedDateRange([fromDate, toDate])}
                onChange={handleDatesChange}
                presets={getDateRangePresets()}
                maxDate={MAX_DATE_FOR_DATE_PICKER}
              />
            </Box>
          </Box>
          <Box display="flex" flex={3}>
            <Box flex={1}>
              <TextInput
                label="Amount"
                type="number"
                name="min-amount"
                placeholder="Enter min amount"
                value={minAmount?.toString() ?? ''}
                validationState={minAmountValidationState}
                errorText={minAmountErrorMsg}
                onChange={({ value }) => amountChangeHandler(value, 'min')}
              />
            </Box>
            <Text
              marginX="spacing.5"
              position="relative"
              top="12px"
              alignSelf="center"
              size="large"
            >
              To
            </Text>
            <Box flex={1}>
              <TextInput
                position="relative"
                top="27px"
                label=""
                type="number"
                name="max-amount"
                placeholder="Enter max amount"
                value={maxAmount?.toString() ?? ''}
                validationState={maxAmountValidationState}
                errorText={maxAmountErrorMsg}
                onChange={({ value }) => amountChangeHandler(value, 'max')}
              />
            </Box>
          </Box>
        </Box>
        <Box
          marginBottom="spacing.5"
          display="flex"
          gap="spacing.5"
          flexDirection={{ base: 'column', xl: 'row' }}
          alignItems={{ base: 'flex-start', xl: 'flex-end' }}
        >
          <Box display="flex" flex={4} gap="spacing.5" flexDirection={{ base: 'column', l: 'row' }}>
            <Box display="flex" flex={1.3} gap="spacing.5">
              <Box flex={1}>
                <Dropdown selectionType="multiple">
                  <AutoComplete
                    label="Transaction Type"
                    placeholder="Select Transaction Type"
                    name="action"
                    value={transactionType}
                    onChange={({ values }) =>
                      setBillsFilterTransaction(values as TransactionType[])
                    }
                  />
                  <DropdownOverlay>
                    <ActionList>
                      {Object.values(TransactionTypes).map(({ Name: name, Value: value }) => (
                        <ActionListItem key={value} title={name} value={value} />
                      ))}
                    </ActionList>
                  </DropdownOverlay>
                </Dropdown>
              </Box>
            </Box>
            <Box display="flex" flex={2} gap="spacing.3" alignItems="flex-end">
              <Box minWidth="134px">
                <Dropdown selectionType="single">
                  <SelectInput
                    label=""
                    placeholder="Search By"
                    name="searchby"
                    accessibilityLabel="searchby"
                    value={selectedSearchOption}
                    onChange={handleSearchByOptionChange}
                  />
                  <DropdownOverlay>
                    <ActionList>
                      {Object.values(BillSearchByOptions).map(({ Name: name, Value: value }) => (
                        <ActionListItem key={name} title={name} value={value} />
                      ))}
                    </ActionList>
                  </DropdownOverlay>
                </Dropdown>
              </Box>
              <Box flex={1}>
                <TextInput
                  label=""
                  name="searchfield"
                  placeholder="Search here"
                  accessibilityLabel="searchfield"
                  validationState={searchValidationState}
                  errorText={searchErrorMsg}
                  value={getSearchInputValue(selectedSearchOption)}
                  onChange={({ value }) => searchHandler(selectedSearchOption, value)}
                />
              </Box>
            </Box>
          </Box>
          <Box
            display="flex"
            alignItems="flex-end"
            gap="spacing.7"
            flex={3}
            flexDirection={{ base: 'column', m: 'row' }}
          >
            <Box flex={1}>
              <Dropdown>
                <AutoComplete
                  label="Select Store Group / Stores"
                  inputValue={storeGroupInputValue}
                  placeholder="Search Group Name"
                  value={selectedGroup}
                  onInputValueChange={({ value }) => setStoreGroupInputValue(value as string)}
                  onChange={({ values }) => {
                    const storeIds = storeGroups
                      ?.find((group) => group.id === values[0])
                      ?.stores?.map((store) => store.id);
                    setBillsStoreIds(storeIds || []);
                    setSelectedGroup(values[0]);
                  }}
                />
                <DropdownOverlay zIndex={zIndicesMap.modal}>
                  {/* ActionList is not accepting single child as children */}
                  {/* @ts-expect-error:next-line */}
                  <ActionList>
                    <ActionListSection title="Group Name (Number of Stores)">
                      {storeGroupOptions?.map((item: StoreGroup, index: number) => (
                        <ActionListItem
                          key={item.id + String(index)}
                          title={`${item.name} (${item.storesCount})`}
                          value={item.id}
                        />
                      ))}
                    </ActionListSection>
                  </ActionList>
                  <DropdownFooter>
                    <Link icon={ArrowRightIcon} iconPosition="right" onClick={openModal}>
                      Manually Select Stores
                    </Link>
                  </DropdownFooter>
                </DropdownOverlay>
              </Dropdown>
            </Box>
            <Divider marginTop="auto" height="spacing.8" orientation="vertical" />
            <Box display="flex" gap="spacing.5" flex={1}>
              <Box flex={1}>
                <Button
                  isFullWidth
                  color="primary"
                  size="medium"
                  type="button"
                  variant="primary"
                  marginRight="spacing.5"
                  onClick={fetchFilteredBillsData}
                  isDisabled={Boolean(minAmountErrorMsg || maxAmountErrorMsg || searchErrorMsg)}
                >
                  Search
                </Button>
              </Box>
              <Box flex={1}>
                <Button
                  isFullWidth
                  color="primary"
                  size="medium"
                  type="button"
                  variant="secondary"
                  onClick={() => {
                    setSelectedSearchOption('');
                    resetBillsFilter();
                  }}
                >
                  Reset
                </Button>
              </Box>
            </Box>
          </Box>
        </Box>
      </Box>
    </Box>
  );
};

export default BillsSearchComponent;
