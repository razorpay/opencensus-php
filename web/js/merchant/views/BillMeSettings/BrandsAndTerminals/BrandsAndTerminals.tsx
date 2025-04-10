import React, { useEffect } from 'react';
import {
  Box,
  Card,
  CardBody,
  Tabs,
  TabList,
  TabItem,
  TabPanel,
  Divider,
} from '@razorpay/blade/components';

import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';

import BrandsTableContainer from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer';
import TerminalsTableContainer from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer';
import { useBrandsTablePayloadStore } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/BrandsTableContainer/stores/brandsTablePayloadStore';
import { useTerminalsTablePayloadStore } from 'merchant/views/BillMeSettings/BrandsAndTerminals/containers/TerminalsTableContainer/stores/terminalsTablePayloadStore';
import Breadcrumbs from 'merchant/views/BillMeSettings/common/components/Breadcrumbs';
import ErrorPage from 'merchant/views/BillMeSettings/common/components/ErrorPage';
import { PAGE_BREADCRUMBS } from './constants';

const BrandsAndTerminals = (): React.ReactElement => {
  const { resetBrandsPayloadFilters } = useBrandsTablePayloadStore();
  const { resetTerminalsPayloadFilters } = useTerminalsTablePayloadStore();

  useEffect(() => {
    return () => {
      resetBrandsPayloadFilters();
      resetTerminalsPayloadFilters();
    };
  }, []);

  return (
    <Card padding="spacing.0" backgroundColor="surface.background.gray.moderate" data-analytics-name="digital-billing-settings-section">
      <CardBody>
        <Box padding="spacing.7">
          <Breadcrumbs items={PAGE_BREADCRUMBS} backPath="/account-settings" />
        </Box>
        <Tabs isLazy variant="borderless" data-analytics-name="brands-and-terminals">
          <Box paddingX="spacing.7">
            <TabList>
              <TabItem value="brands">Store Brands</TabItem>
              <TabItem value="terminals">Billing Terminals</TabItem>
            </TabList>
          </Box>
          <Divider variant="normal" />
          <TabPanel value="brands">
            <ErrorBoundary
              FallbackComponent={() => <ErrorPage />}
              rank={Ranks.P0}
              team={Teams.BILLME}
              resetOnProps
            >
              <BrandsTableContainer />
            </ErrorBoundary>
          </TabPanel>
          <TabPanel value="terminals">
            <ErrorBoundary
              FallbackComponent={() => <ErrorPage />}
              rank={Ranks.P0}
              team={Teams.BILLME}
              resetOnProps
            >
              <TerminalsTableContainer />
            </ErrorBoundary>
          </TabPanel>
        </Tabs>
      </CardBody>
    </Card>
  );
};

export default BrandsAndTerminals;
