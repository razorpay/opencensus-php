import React, { useEffect } from 'react';
import {
  Card,
  CardBody,
  Box,
  Accordion,
  AccordionItem,
  AccordionItemHeader,
  AccordionItemBody,
  Heading,
  Spinner,
} from '@razorpay/blade/components';
import { useQueryClient } from '@tanstack/react-query';

import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import Breadcrumbs, {
  BreadCrumbType,
} from 'merchant/views/BillMeSettings/common/components/Breadcrumbs';
import ErrorPage from 'merchant/views/BillMeSettings/common/components/ErrorPage';
import StoreGroupsContainer from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer';
import { useStoreGroupsStore } from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/stores/storeGroupsStore';
import StoresTableContainer from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer';
import { useStoresTablePayloadStore } from 'merchant/views/StoreSettings/StoresList/containers/StoresTableContainer/stores/storesTablePayloadStore';
import { PAGE_BREADCRUMBS } from './constants';

const StoresList = (): React.ReactElement => {
  const queryClient = useQueryClient();
  const {
    updateModalStatus,
    selectedStoreGroupInfo,
    setSelectedStoreGroupInfo,
    resetStoreGroupsList,
  } = useStoreGroupsStore();
  const { storesFilterPayload, resetPayloadFilters } = useStoresTablePayloadStore();

  useEffect(() => {
    return () => {
      updateModalStatus(null);
      setSelectedStoreGroupInfo(null);
      resetStoreGroupsList();
      resetPayloadFilters();
      queryClient.removeQueries({
        queryKey: ['store_groups_list_data'],
      });
      queryClient.removeQueries({
        queryKey: ['deleted_stores_data'],
      });
      queryClient.removeQueries({
        queryKey: [
          'stores_table_data',
          {
            offset: storesFilterPayload.offset,
            limit: storesFilterPayload.limit,
            selectedStoreGroupId: selectedStoreGroupInfo?.id,
          },
        ],
      });
    };
  }, []);

  const renderLoader = () => (
    <Box display="flex" alignItems="center" justifyContent="center" height="100%">
      <Spinner accessibilityLabel="Stores table page loading" />
    </Box>
  );

  return (
    <Card padding="spacing.0" backgroundColor="surface.background.gray.moderate">
      <CardBody>
        <Box padding="spacing.7">
          <Breadcrumbs items={PAGE_BREADCRUMBS} backPath="/account-settings" />
        </Box>
        <Box
          display="flex"
          gap="spacing.5"
          paddingX="spacing.5"
          flexDirection={{ base: 'column', l: 'row' }}
        >
          <Box flex={1} paddingBottom="spacing.5">
            <Accordion variant="filled" expandedIndex={0}>
              <AccordionItem>
                <AccordionItemHeader>
                  <Heading size="large">Store Groups</Heading>
                </AccordionItemHeader>
                <AccordionItemBody>
                  <ErrorBoundary
                    FallbackComponent={() => <ErrorPage />}
                    rank={Ranks.P0}
                    team={Teams.BILLME_INTEGRATION}
                    resetOnProps
                  >
                    <StoreGroupsContainer isAccordionExpanded />
                  </ErrorBoundary>
                </AccordionItemBody>
              </AccordionItem>
            </Accordion>
          </Box>
          <Box flex={2} paddingBottom="spacing.5">
            {selectedStoreGroupInfo ? (
              <ErrorBoundary
                FallbackComponent={() => <ErrorPage />}
                rank={Ranks.P0}
                team={Teams.BILLME_INTEGRATION}
                resetOnProps
              >
                <StoresTableContainer />
              </ErrorBoundary>
            ) : (
              renderLoader()
            )}
          </Box>
        </Box>
      </CardBody>
    </Card>
  );
};

export default StoresList;
