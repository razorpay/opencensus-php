import React, { useEffect } from 'react';
import { Card, CardBody, Box, Tabs, TabList, TabItem, TabPanel } from '@razorpay/blade/components';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import errorService from '@razorpay/universe-cli/errorService';
import { useQuery } from '@tanstack/react-query';

import { graphqlRequest } from '@apps/digital-bills/src/utils/graphql';
import BillsTableContainer from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/BillsTableContainer';
import StoreTableContainer from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/StoreTableContainer';
import { useBillsTablePayloadStore } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/stores/billsTablePayloadStore';
import { useStoreTablePayloadStore } from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/stores/storeTablePayloadStore';
import ErrorPage from '@apps/digital-bills/src/common/components/ErrorPage';
import { DIGITAL_BILLS, ERROR_PAGE_DESCRIPTION } from '@apps/digital-bills/src/utils/constants';
import {
  STORE_GROUP_DATA_QUERY,
  STORES_DATA_QUERY,
} from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/queries';

import type {
  StoreGroupsDataResponse,
  StoresDataResponse,
} from '@apps/digital-bills/src/views/BillsView/containers/TableContainer/types';

const TableContainer = () => {
  const {
    data: storeGroupsResponse,
    refetch: refetchStoreGroups,
    isFetching: isStoreGroupsFetching,
    isError: isErrorInFetchingStoreGroups,
  } = useQuery<StoreGroupsDataResponse>({
    refetchOnWindowFocus: false,
    queryKey: ['store_groups_list'],
    queryFn: () =>
      graphqlRequest({
        document: STORE_GROUP_DATA_QUERY,
        variables: {
          limit: 25,
          offset: 0,
        },
      }),
  });

  const {
    data: storesResponse,
    refetch: refetchStores,
    isFetching: isStoresFetching,
    isError: isErrorInFetchingStores,
  } = useQuery<StoresDataResponse>({
    refetchOnWindowFocus: false,
    queryKey: ['stores_table_data'],
    queryFn: () => {
      const variables = {
        offset: 0,
        limit: 1,
        isDeleted: false,
      };
      return graphqlRequest({
        document: STORES_DATA_QUERY,
        variables,
      });
    },
  });

  const { resetBillsFilter } = useBillsTablePayloadStore();
  const { resetStoreFilter } = useStoreTablePayloadStore();

  useEffect(() => {
    return () => {
      resetBillsFilter();
      resetStoreFilter();
    };
  }, []);

  return (
    <Card
      backgroundColor="surface.background.gray.moderate"
      marginTop="spacing.7"
      padding="spacing.0"
    >
      <CardBody height="100%">
        <Box height="100%" marginBottom="spacing.6" marginTop="spacing.2" marginX="spacing.6">
          <Tabs orientation="horizontal" size="medium" variant="bordered" isLazy>
            <TabList>
              <TabItem value="bills">Bills</TabItem>
              <TabItem value="store-data">Store Data</TabItem>
            </TabList>
            <TabPanel value="bills">
              <ErrorBoundary
                rank={errorService.ErrorRank.P0}
                tags={{ module: DIGITAL_BILLS }}
                fallbackComponent={<ErrorPage description={ERROR_PAGE_DESCRIPTION} />}
              >
                <BillsTableContainer
                  storesResponse={storesResponse}
                  storeGroupsResponse={storeGroupsResponse}
                  isStoresInfoLoading={isStoreGroupsFetching || isStoresFetching}
                  hasErrorInStoresInfo={isErrorInFetchingStoreGroups || isErrorInFetchingStores}
                  retryFn={isErrorInFetchingStoreGroups ? refetchStoreGroups : refetchStores}
                />
              </ErrorBoundary>
            </TabPanel>
            <TabPanel value="store-data">
              <ErrorBoundary
                rank={errorService.ErrorRank.P0}
                tags={{ module: DIGITAL_BILLS }}
                fallbackComponent={<ErrorPage description={ERROR_PAGE_DESCRIPTION} />}
              >
                <StoreTableContainer
                  storesResponse={storesResponse}
                  storeGroupsResponse={storeGroupsResponse}
                  isStoresInfoLoading={isStoreGroupsFetching || isStoresFetching}
                  hasErrorInStoresInfo={isErrorInFetchingStoreGroups || isErrorInFetchingStores}
                  retryFn={isErrorInFetchingStoreGroups ? refetchStoreGroups : refetchStores}
                />
              </ErrorBoundary>
            </TabPanel>
          </Tabs>
        </Box>
      </CardBody>
    </Card>
  );
};

export default TableContainer;
