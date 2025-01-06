import React, { useState } from 'react';
import {
  Box,
  Spinner,
  Card,
  CardBody,
  Tabs,
  TabList,
  TabItem,
  Divider,
  Button,
  TrashIcon,
  EditIcon,
  useToast,
  Indicator,
  Text,
  Link,
  RefreshIcon,
} from '@razorpay/blade/components';
import { useQuery, useMutation } from '@tanstack/react-query';
import { useParams, useNavigate } from 'react-router-dom';

import { REACT_QUERY_CACHE_KEYS } from 'common/constant';
import { graphqlRequest, graphqlRequestMutation } from 'common/services/graphql/graphql-client';
import Breadcrumbs from 'merchant/views/BillMeSettings/common/components/Breadcrumbs';
import DeleteStoreModal from 'merchant/views/StoreSettings/StoreDetails/components/DeleteStoreModal';
import DigitalBillingInfo from 'merchant/views/StoreSettings/StoreDetails/components/DigitalBillingInfo';
import StoreOverview from 'merchant/views/StoreSettings/StoreDetails/components/StoreOverview';
import { STORE_DETAILS_TABS } from 'merchant/views/StoreSettings/StoreDetails/constants';
import { DELETE_STORE_BY_ID } from 'merchant/views/StoreSettings/StoreDetails/mutations';
import { STORE_BY_ID } from 'merchant/views/StoreSettings/StoreDetails/queries';
import { getPageBreadcrumbs } from 'merchant/views/StoreSettings/StoreDetails/utils';

const StoreDetails = (): React.ReactElement => {
  const [activeTab, setActiveTab] = useState<string>(STORE_DETAILS_TABS.OVERVIEW);
  const [shouldShowDeleteModal, setShouldShowDeleteModal] = useState<boolean>(false);
  const { storeId } = useParams();
  const navigate = useNavigate();
  const { show } = useToast();

  const {
    data: storeInfoResponse,
    isFetching,
    isError: isErrorInFetchingStoreInfo,
    refetch,
  } = useQuery({
    refetchOnWindowFocus: false,
    queryKey: [REACT_QUERY_CACHE_KEYS.STORE_INFO, storeId],
    queryFn: () =>
      graphqlRequest({
        document: STORE_BY_ID,
        variables: { id: storeId },
      }),
  });

  const { mutate: deleteStore, isLoading: isStoreDeleteLoading } = useMutation({
    mutationFn: () =>
      graphqlRequestMutation({
        document: DELETE_STORE_BY_ID,
        variables: { id: storeId },
      }),
    onSuccess: () => {
      show({
        type: 'informational',
        color: 'positive',
        content: 'A Store has been deleted.',
      });
      setShouldShowDeleteModal(false);
      navigate('/store-settings/stores-list');
    },
    onError: () => {
      show({
        type: 'informational',
        color: 'negative',
        content: 'Something went wrong. Please try again!',
      });
    },
  });

  const storeDetails = storeInfoResponse?.storeById;

  if (isFetching) {
    return (
      <Box display="flex" alignItems="center" justifyContent="center" height="100%">
        <Spinner accessibilityLabel="Store details page loading" />
      </Box>
    );
  }

  if (isErrorInFetchingStoreInfo) {
    return (
      <Box display="flex" alignItems="center" justifyContent="center" height="100%" gap="spacing.3">
        <Text size="medium" color="interactive.text.negative.normal">
          Error in fetching store info
        </Text>
        <Link
          size="medium"
          variant="button"
          icon={RefreshIcon}
          iconPosition="right"
          onClick={() => refetch()}
        >
          Retry
        </Link>
      </Box>
    );
  }

  return (
    <>
      {shouldShowDeleteModal && (
        <DeleteStoreModal
          isLoading={isStoreDeleteLoading}
          onSubmit={deleteStore}
          modalProps={{
            isOpen: shouldShowDeleteModal,
            onDismiss: () => setShouldShowDeleteModal(false),
          }}
        />
      )}
      <Card padding="spacing.0" backgroundColor="surface.background.gray.moderate">
        <CardBody>
          <Box padding="spacing.7">
            <Breadcrumbs
              items={getPageBreadcrumbs(storeDetails?.name)}
              backPath="/store-settings/stores-list"
            />
          </Box>
          <Box
            display="flex"
            justifyContent="space-between"
            paddingX="spacing.7"
            flexDirection={{ base: 'column', l: 'row' }}
          >
            <Box flex="1">
              <Tabs
                isLazy
                variant="borderless"
                value={activeTab}
                onChange={(tab) => setActiveTab(tab)}
              >
                <TabList>
                  <TabItem value={STORE_DETAILS_TABS.OVERVIEW}>Overview</TabItem>
                  {storeDetails?.storeInfo?.linkedProducts?.includes('DIGITAL_BILLING') ? (
                    <TabItem value={STORE_DETAILS_TABS.DIGITAL_BILLING}>Digital Billing</TabItem>
                  ) : null}
                </TabList>
              </Tabs>
            </Box>
            {storeDetails?.dates?.deletedAt ? (
              <Indicator color="negative" size="large">
                Deleted
              </Indicator>
            ) : (
              <Box
                display="flex"
                gap="spacing.5"
                marginY="spacing.3"
                flex="1"
                justifyContent="flex-end"
              >
                <Button
                  color="negative"
                  iconPosition="left"
                  icon={TrashIcon}
                  onClick={() => setShouldShowDeleteModal(true)}
                >
                  Delete
                </Button>
                <Button
                  iconPosition="left"
                  icon={EditIcon}
                  onClick={() => navigate(`/store-settings/store-create?id=${storeId}`)}
                >
                  Edit
                </Button>
              </Box>
            )}
          </Box>
          <Box paddingX="spacing.7">
            <Divider variant="normal" />
          </Box>
          <Box backgroundColor="surface.background.gray.intense" marginTop="spacing.7">
            {activeTab === STORE_DETAILS_TABS.OVERVIEW && (
              <StoreOverview fetchedStoreInfo={storeDetails} />
            )}
            {activeTab === STORE_DETAILS_TABS.DIGITAL_BILLING && (
              <DigitalBillingInfo fetchedStoreInfo={storeDetails} />
            )}
          </Box>
        </CardBody>
      </Card>
    </>
  );
};

export default StoreDetails;
