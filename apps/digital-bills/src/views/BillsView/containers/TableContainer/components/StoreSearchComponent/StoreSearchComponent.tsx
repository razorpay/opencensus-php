import React, { useEffect, useMemo, useState } from 'react';
import {
  useTheme,
  Box,
  Button,
  Divider,
  DatePicker,
  Dropdown,
  DropdownOverlay,
  SelectInput,
  ActionList,
  ActionListItem,
  ActionListSection,
  AutoComplete,
  TextInput,
  Text,
  Link,
  DropdownFooter,
  ArrowRightIcon,
} from '@razorpay/blade/components';

import {
  zIndicesMap,
  StoreSearchByOptions,
  StoreStatusTypes,
  MAX_DATE_FOR_DATE_PICKER,
} from '@apps/digital-bills/src/utils/constants';
import { useBillsTablePayloadStore } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/stores/billsTablePayloadStore';
import { useStoreTablePayloadStore } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/stores/storeTablePayloadStore';
import {
  getDateRangePresets,
  getSelectedDatesFromPicker,
  getUpdatedDateRange,
} from '@apps/digital-bills/src/utils/helpers/getDateRangePresets';
import useSearchByOptions from '@apps/digital-bills/src/common/hooks/useSearchByOptions';
import useMinMaxAmount from '@apps/digital-bills/src/common/hooks/useMinMaxAmount';
import { DEFAULT_STORE_GROUP } from '@apps/digital-bills/src/common/components/StoreFilterModal/constants';
import getTimeStampDiff from '@apps/digital-bills/src/utils/helpers/getTimeStampDiff';

import type {
  StoreGroup,
  StoresDataResponse,
  StoreGroupsDataResponse,
  StoreSearchInput,
  StoreStatus,
} from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';
import type {
  DateRangePropsType,
  StatusPropsType,
  AmountPropsType,
  SearchPropsType,
} from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/components/StoreSearchComponent/types';

type StoreSearchComponentProps = {
  dateRangeProps: DateRangePropsType;
  statusProps: StatusPropsType;
  amountProps: AmountPropsType;
  searchProps: SearchPropsType;
  resetStoreFilter: () => void;
  fetchFilteredStoreData: () => void;
  storesResponse: StoresDataResponse | undefined;
  storeGroupsResponse: StoreGroupsDataResponse | undefined;
};

const StoreSearchComponent = ({
  amountProps: { minAmount, maxAmount, setStoreFilterMinAmount, setStoreFilterMaxAmount },
  statusProps: { selectedStoreStatus, setStoreFilterStatus },
  searchProps: { searchInputs, setStoreFilterSearch },
  dateRangeProps: { selectedDateRange, setStoreFilterDateRange },
  resetStoreFilter,
  fetchFilteredStoreData,
  storesResponse,
  storeGroupsResponse,
}: StoreSearchComponentProps) => {
  const [selectedSearchOption, setSelectedSearchOption] = useState('');
  const [showMaxRangeError, setShowMaxRangeError] = useState(false);
  const { platform } = useTheme();
  const isMobile = platform === 'onMobile';
  const {
    minAmountValidationState,
    maxAmountValidationState,
    minAmountErrorMsg,
    maxAmountErrorMsg,
    amountChangeHandler,
  } = useMinMaxAmount(minAmount, maxAmount, setStoreFilterMinAmount, setStoreFilterMaxAmount);

  const {
    searchValidationState,
    searchErrorMsg,
    searchByOptionHandler,
    searchHandler,
    getSearchInputValue,
  } = useSearchByOptions<StoreSearchInput>(
    searchInputs,
    selectedSearchOption,
    setSelectedSearchOption,
    setStoreFilterSearch,
  );

  const { setStoreFilterModalOpen } = useBillsTablePayloadStore();
  const { storeGroupInputValue, setStoreGroupInputValue, setSelectedStoreIds } =
    useStoreTablePayloadStore();
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
    setStoreFilterDateRange(fromDate, toDate);
    const daysDiff = getTimeStampDiff(toDate, fromDate, 'days');
    if (daysDiff >= 90) {
      setShowMaxRangeError(true);
    } else if (showMaxRangeError) {
      setShowMaxRangeError(false);
    }
  };
  const openModal = () => setStoreFilterModalOpen(true);

  const handleSearchByOptionChange = ({ values }) => {
    if (selectedSearchOption) {
      setStoreFilterSearch(selectedSearchOption, '');
    }
    searchByOptionHandler(values[0]);
  };

  const isSearchDisabled = Boolean(
    minAmountErrorMsg || maxAmountErrorMsg || searchErrorMsg || showMaxRangeError,
  );

  const handleStoreGroupSelection = ({ values }) => {
    const storeIds = storeGroups
      ?.find((group) => group.id === values[0])
      ?.stores?.map((store) => store.id);
    setSelectedStoreIds(storeIds || []);
    setSelectedGroup(values[0]);
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
            <Box flex={1}>
              <Dropdown selectionType="multiple">
                <AutoComplete
                  label="Status"
                  placeholder="Select Store Status"
                  name="action"
                  value={selectedStoreStatus}
                  onChange={({ values }) => setStoreFilterStatus(values as StoreStatus[])}
                />
                <DropdownOverlay>
                  <ActionList>
                    {Object.values(StoreStatusTypes).map(({ Name: name, Value: value }) => (
                      <ActionListItem key={value} title={name} value={value} />
                    ))}
                  </ActionList>
                </DropdownOverlay>
              </Dropdown>
            </Box>
            <Box flex={1}>
              <DatePicker
                /* @ts-expect-error:next-line */
                label={{
                  start: 'Duration',
                }}
                /* @ts-expect-error:next-line */
                selectionType="range"
                value={getUpdatedDateRange(selectedDateRange)}
                onChange={handleDatesChange}
                presets={getDateRangePresets()}
                maxDate={MAX_DATE_FOR_DATE_PICKER}
                validationState={showMaxRangeError ? 'error' : 'none'}
                // @ts-expect-error
                errorText={{ start: "Range can't be more than 90 days" }}
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
          alignItems="flex-end"
        >
          <Box display="flex" flex={4} gap="spacing.5" flexDirection={{ base: 'column', m: 'row' }}>
            <Box flex={1}>
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
                    {Object.values(StoreSearchByOptions).map(({ name, value }) => (
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
          <Box
            display="flex"
            flex={3}
            alignItems="flex-end"
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
                  onChange={handleStoreGroupSelection}
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
            <Divider
              orientation={isMobile ? 'horizontal' : 'vertical'}
              variant="normal"
              height="spacing.8"
              thickness="thinner"
              marginX={isMobile ? 'spacing.0' : 'spacing.7'}
              marginTop="auto"
            />
            <Box display="flex" gap="spacing.5" flex={1}>
              <Box flex={1}>
                <Button
                  isFullWidth
                  color="primary"
                  size="medium"
                  type="button"
                  variant="primary"
                  marginRight="spacing.5"
                  onClick={fetchFilteredStoreData}
                  isDisabled={isSearchDisabled}
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
                    resetStoreFilter();
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

export default StoreSearchComponent;
