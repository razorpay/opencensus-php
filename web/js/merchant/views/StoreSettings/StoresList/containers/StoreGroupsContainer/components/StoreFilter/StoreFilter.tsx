import React, { ReactElement, useState, useEffect, useRef } from 'react';
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
  useToast,
} from '@razorpay/blade/components';
import { useQuery } from '@tanstack/react-query';

import { zIndicesMap, REACT_QUERY_CACHE_KEYS } from 'common/constant';
import { graphqlRequest } from '@federated/apps/shell/graphql';
import { useIntersectionObserver } from 'merchant/hooks/useIntersectionObserver';
import { verifyGqlErrorResponse } from 'merchant/views/BillMeSettings/common/utils';
import {
  STORES_LIST_DATA_QUERY,
  STORES_STATES_AND_CITIES_QUERY,
} from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/queries';
import {
  STORES_SEARCH_BY_FIELDS,
  STORES_LIMIT,
  STORE_NAME,
  STORES_FETCH_ERROR_MESSAGE,
  STORES_STATES_CITIES_ERROR_MESSAGE,
  STORES_STATES_CITIES_LOADER,
  STORES_LIST_LOADER,
} from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/components/StoreFilter/constants';
import MultiSelectSlot from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/components/StoreFilter/MultiSelectSlot';
import { getLeftSlots } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/components/StoreFilter/utils';

import type {
  Filters,
  StoresDataResponse,
  Store,
  StoresType,
  StoresStatesAndCitiesDataResponse,
  StoreFilterProps,
} from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/components/StoreFilter/types';
import type { StoreSearchColumnType } from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/types';

const StoreFilter = ({ onSelectStores, selectedStores }: StoreFilterProps): ReactElement => {
  const [filters, setFilters] = useState<Filters>({
    states: [],
    cities: [],
    searchColumn: STORE_NAME,
    searchTerm: '',
    offset: 0,
  });
  const [stores, setStores] = useState<StoresType>({});
  const [shouldTriggerRefetch, setShouldTriggerRefetch] = useState<boolean>(false);

  const storesListRef = useRef(null);
  const isOnScreen = useIntersectionObserver(storesListRef);
  const toast = useToast();

  const {
    data: statesAndCitiesResponse,
    isFetching: isStatesAndCitiesFetching,
    isError: isErrorInFetchingStatesAndCities,
    error: statesAndCitiesError,
  } = useQuery<StoresStatesAndCitiesDataResponse>({
    refetchOnWindowFocus: false,
    queryKey: [REACT_QUERY_CACHE_KEYS.STORES_STATES_AND_CITIES_DATA],
    retry: false,
    queryFn: () =>
      graphqlRequest({
        document: STORES_STATES_AND_CITIES_QUERY,
      }),
  });

  const handleStoresResponse = (response: StoresDataResponse) => {
    const storesData = response.stores.stores;
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
    setFilters({ ...filters, offset: prevOffset + storesData.length });
  };

  const {
    data: storesResponse,
    isFetching: isStoresFetching,
    refetch,
    isError: isErrorInFetchingStoresList,
    error: storesListError,
  } = useQuery<StoresDataResponse>({
    refetchOnWindowFocus: false,
    retry: false,
    queryKey: [
      REACT_QUERY_CACHE_KEYS.STORES_LIST_DATA,
      {
        states: filters.states,
        cities: filters.cities,
      },
    ],
    queryFn: () => {
      const { states, cities, searchTerm, searchColumn, offset } = filters;
      const variables = {
        searchTerm,
        searchColumn,
        limit: STORES_LIMIT,
        // If shouldTriggerRefetch is true, then reset the offset to 0 to neglect the offset set due to infinite scrolling
        offset: shouldTriggerRefetch ? 0 : offset,
        states: states.length ? states : null,
        cities: cities.length ? cities : null,
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
    const isStoresPartiallyFetched = filters.offset < totalItemsCount;
    if (!isStoresFetching && isOnScreen && !shouldTriggerRefetch && isStoresPartiallyFetched) {
      refetch();
    }
  }, [isStoresFetching, isOnScreen, filters.offset, totalItemsCount, shouldTriggerRefetch]);

  useEffect(() => {
    if (shouldTriggerRefetch) {
      refetch();
    }
  }, [shouldTriggerRefetch]);

  const leftSlots = getLeftSlots({
    states: statesAndCitiesResponse?.storesStatesAndCitiesByMerchantId?.states || [],
    cities: statesAndCitiesResponse?.storesStatesAndCitiesByMerchantId?.cities || [],
  });

  const isStatesAndCitiesAccessDenied = verifyGqlErrorResponse(statesAndCitiesError);
  const isStoresListAccessDenied = verifyGqlErrorResponse(storesListError);

  const renderLoader = (accessibilityLabel = '') => (
    <Box display="flex" justifyContent="center" height="100%">
      <Spinner accessibilityLabel={accessibilityLabel} />
    </Box>
  );

  const renderInfo = (message: string, showAsError = false) => {
    return (
      <Box marginY="spacing.6">
        <Text
          weight="semibold"
          color={showAsError ? 'feedback.text.negative.intense' : 'surface.text.gray.muted'}
          textAlign="center"
        >
          {message}
        </Text>
      </Box>
    );
  };

  const renderStores = () => {
    if (shouldTriggerRefetch && isStoresFetching) {
      return renderLoader(STORES_LIST_LOADER);
    }
    if (isErrorInFetchingStoresList) {
      const errorMessage = isStoresListAccessDenied ? 'Access Denied' : STORES_FETCH_ERROR_MESSAGE;
      return renderInfo(errorMessage, true);
    }

    return (
      <>
        {!isStoresFetching && !Object.keys(stores || {}).length ? (
          renderInfo('No stores found')
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
        {isStoresFetching ? renderLoader(STORES_LIST_LOADER) : null}
      </>
    );
  };

  const renderStatesAndCitiesFilter = () => {
    if (isErrorInFetchingStatesAndCities) {
      const errorMessage = isStatesAndCitiesAccessDenied
        ? 'Access Denied'
        : STORES_STATES_CITIES_ERROR_MESSAGE;
      return renderInfo(errorMessage, true);
    }
    if (isStatesAndCitiesFetching) {
      return renderLoader(STORES_STATES_CITIES_LOADER);
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
        {renderStatesAndCitiesFilter()}
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
          <Button
            icon={SearchIcon}
            onClick={() => {
              setShouldTriggerRefetch(true);
            }}
          />
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
