import React, { useState, useEffect, useRef } from 'react';
import {
  Box,
  ChipGroup,
  Chip,
  Text,
  TextInput,
  SearchIcon,
  Button,
  Spinner,
  Dropdown,
  DropdownOverlay,
  AutoComplete,
  ActionList,
  ActionListItem,
} from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';

import { zIndicesMap } from '@apps/digital-bills/src/utils/constants';
import { graphqlRequest } from '@apps/digital-bills/src/utils/graphql';
import { STORES_SEARCH_BY_FIELDS } from '@apps/digital-bills/src/common/components/StoreFilterModal/constants';
import {
  BRANDS_LIST_DATA_QUERY,
  STORES_LIST_DATA_QUERY,
  STORES_STATES_AND_CITIES_QUERY,
} from '@apps/digital-bills/src/common/components/StoreFilterModal/queries';
import MultiSelectSlot from '@apps/digital-bills/src/common/components/StoreFilterModal/MultiSelectSlot';
import { getLeftSlots } from '@apps/digital-bills/src/common/components/StoreFilterModal/utils';
import { useIntersectionObserver } from '@apps/digital-bills/src/common/hooks/useIntersectionObserver';

import type {
  Filters,
  StoresDataResponse,
  Store,
  StoresType,
  StoresStatesAndCitiesDataResponse,
  StoreSearchColumnType,
  BrandsDataResponse,
} from '@apps/digital-bills/src/common/components/StoreFilterModal/types';

type StoreFilterProps = {
  onSelectStores: (stores: StoresType) => void;
  selectedStores: StoresType;
};

const StoreFilter = ({ onSelectStores, selectedStores }: StoreFilterProps): React.ReactElement => {
  const [filters, setFilters] = useState<Filters>({
    states: [],
    cities: [],
    searchColumn: 'STORE_NAME',
    searchTerm: '',
    brands: [],
  });
  const [stores, setStores] = useState<StoresType>({});
  const [storesListOffset, setStoresListOffset] = useState(0);
  const [shouldTriggerRefetch, setShouldTriggerRefetch] = useState(false);

  const storesListRef = useRef(null);
  const isOnScreen = useIntersectionObserver(storesListRef);

  const {
    data: statesAndCitiesResponse,
    isFetching: isStatesAndCitiesFetching,
    isError: isErrorInFetchingStatesAndCities,
  } = useQuery<StoresStatesAndCitiesDataResponse>({
    refetchOnWindowFocus: false,
    queryKey: ['stores_states_and_cities_data'],
    queryFn: () =>
      graphqlRequest({
        document: STORES_STATES_AND_CITIES_QUERY,
      }),
  });

  const {
    data: brandsResponse,
    isFetching: isBrandsFetching,
    isError: isErrorInFetchingBrands,
  } = useQuery<BrandsDataResponse>({
    refetchOnWindowFocus: false,
    queryKey: ['brands_data'],
    queryFn: () =>
      graphqlRequest({
        document: BRANDS_LIST_DATA_QUERY,
        variables: {
          limit: 25,
          offset: 0,
        },
      }),
  });
  const handleStoresResponse = (response: StoresDataResponse) => {
    const storesData = response?.stores?.stores ?? [];
    const storesInfo = {};
    storesData.forEach((store: Store) => {
      // If store is not already selected, then only add it to the list
      if (!selectedStores[store.id]) {
        const { id, name, storeInfo } = store;
        storesInfo[id] = { label: `${storeInfo.storeCode} - ${name}`, value: id };
      }
    });
    const prevFetchedStores = {};
    let prevOffset = 0;
    // If shouldTriggerRefetch is true, then reset the stores list with the new data appended to the previously selected stores
    // else append the new data to the previously fetched stores along with the selected stores at the top
    if (!shouldTriggerRefetch) {
      for (const store in stores) {
        if (!selectedStores[store]) {
          prevFetchedStores[store] = stores[store];
        }
      }
      prevOffset = response.stores.offset;
    } else {
      setShouldTriggerRefetch(false);
    }
    setStores({ ...selectedStores, ...prevFetchedStores, ...storesInfo });
    setStoresListOffset(prevOffset + storesData.length);
  };

  const {
    data: storesResponse,
    isFetching: isStoresFetching,
    refetch,
    isError: isErrorInFetchingStoresList,
  } = useQuery<StoresDataResponse>({
    refetchOnWindowFocus: false,
    queryKey: [
      'stores_list_data',
      {
        states: filters.states,
        cities: filters.cities,
        brands: filters.brands,
      },
    ],
    queryFn: () => {
      const { states, cities, searchTerm, searchColumn, brands } = filters;
      const variables = {
        searchTerm,
        searchColumn,
        limit: 25,
        // If shouldTriggerRefetch is true, then reset the offset to 0 to neglect the offset set due to infinite scrolling
        offset: shouldTriggerRefetch ? 0 : storesListOffset,
        states: states.length ? states : null,
        cities: cities.length ? cities : null,
        brandIds: brands.length ? brands : null,
        isDeleted: false,
      };
      return graphqlRequest({
        document: STORES_LIST_DATA_QUERY,
        variables,
      });
    },
    onSuccess: handleStoresResponse,
  });

  const totalItemsCount = storesResponse?.stores?.total ?? 0;

  // Infinite scrolling
  useEffect(() => {
    if (
      !isStoresFetching &&
      isOnScreen &&
      !shouldTriggerRefetch &&
      storesListOffset < totalItemsCount
    ) {
      refetch();
    }
  }, [isStoresFetching, isOnScreen, storesListOffset, totalItemsCount, shouldTriggerRefetch]);

  useEffect(() => {
    if (shouldTriggerRefetch) {
      refetch();
    }
  }, [shouldTriggerRefetch]);

  const leftSlots = getLeftSlots({
    brand:
      brandsResponse?.storeBrands?.storeBrands?.map((brand) => ({
        label: brand.name,
        value: brand.id,
      })) || [],
    state:
      statesAndCitiesResponse?.storesStatesAndCitiesByMerchantId?.states?.map((state) => ({
        label: state,
        value: state,
      })) || [],
    city:
      statesAndCitiesResponse?.storesStatesAndCitiesByMerchantId?.cities?.map((city) => ({
        label: city,
        value: city,
      })) || [],
  });

  const renderLoader = (accessibilityLabel = '') => (
    <Box display="flex" justifyContent="center" height="100%">
      <Spinner accessibilityLabel={accessibilityLabel} />
    </Box>
  );

  const renderInfo = (message: string) => {
    return (
      <Text weight="semibold" color="surface.text.gray.muted" textAlign="center">
        {message}
      </Text>
    );
  };

  const renderStores = () => {
    if (shouldTriggerRefetch && isStoresFetching) {
      return renderLoader('Stores list loading');
    }
    if (isErrorInFetchingStoresList) {
      return renderInfo('Error in fetching stores. Please try again later.');
    }
    return (
      <>
        {!isStoresFetching && !Object.keys(stores || {}).length ? (
          <Text weight="semibold" color="surface.text.gray.muted" textAlign="center">
            No stores found
          </Text>
        ) : (
          <ChipGroup
            onChange={({ values }): void => {
              const storesInfo = {};
              values.forEach((storeId) => {
                storesInfo[storeId] = stores[storeId];
              });
              onSelectStores(storesInfo);
            }}
            accessibilityLabel="Stores"
            marginTop="spacing.4"
            selectionType="multiple"
            value={Object.keys(selectedStores)}
          >
            {Object.values(stores).map((store) => (
              <Chip key={store.value} value={store.value}>
                {store.label}
              </Chip>
            ))}
          </ChipGroup>
        )}
        {isStoresFetching ? renderLoader('Stores list loading') : null}
      </>
    );
  };

  const renderFilter = () => {
    if (isStatesAndCitiesFetching || isBrandsFetching) {
      return renderLoader('Store filters loading');
    }
    if (isErrorInFetchingStatesAndCities || isErrorInFetchingBrands) {
      return renderInfo('Error in fetching filters. Please try again later.');
    }
    return leftSlots.slot.map((slot) => (
      <MultiSelectSlot
        name={slot.value}
        key={slot.value}
        title={slot.label}
        options={slot.options}
        onChange={({ name, values }): void => {
          setFilters({ ...filters, [name]: values });
          setShouldTriggerRefetch(true);
        }}
      />
    ));
  };

  return (
    <Box display="flex" flexDirection={{ base: 'column', m: 'row' }}>
      <Box
        gap="spacing.4"
        flex="1"
        display="flex"
        flexDirection="column"
        backgroundColor="surface.background.gray.moderate"
        padding="spacing.7"
        height="300px"
        overflow="auto"
      >
        {renderFilter()}
      </Box>
      <Box flex="2" padding="spacing.7" height="300px" overflow="auto">
        <Box display="flex" gap="spacing.3" alignItems="flex-end">
          <Box flex="1.2">
            <Dropdown>
              <AutoComplete
                label="Search By"
                placeholder="Select Search By field"
                name="action"
                value={filters.searchColumn}
                onChange={({ values }) =>
                  setFilters({ ...filters, searchColumn: values[0] as StoreSearchColumnType })
                }
              />
              <DropdownOverlay zIndex={zIndicesMap.dropdownOverlay}>
                <ActionList>
                  {Object.keys(STORES_SEARCH_BY_FIELDS).map((type) => (
                    <ActionListItem
                      key={type}
                      title={STORES_SEARCH_BY_FIELDS[type].label}
                      value={type}
                    />
                  ))}
                </ActionList>
              </DropdownOverlay>
            </Dropdown>
          </Box>
          <Box flex="2">
            <TextInput
              placeholder="Search"
              accessibilityLabel="Store Search field"
              onChange={({ value }) => setFilters({ ...filters, searchTerm: value as string })}
            />
          </Box>
          <Button icon={SearchIcon} onClick={() => setShouldTriggerRefetch(true)} />
        </Box>
        <Text color="surface.text.gray.subtle" marginTop="spacing.4">
          Stores
        </Text>
        {renderStores()}
        <Box ref={storesListRef} />
      </Box>
    </Box>
  );
};

export default StoreFilter;
