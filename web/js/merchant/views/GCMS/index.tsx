import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { Tabs, TabList, TabItem, Box, Text } from '@razorpay/blade/components';
import { Route, Routes, Navigate, useLocation, useNavigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

import Programs from 'merchant/views/GCMS/Programs';
import ProgramPage from 'merchant/views/GCMS/Programs/ProgramPage';
import Resellers from 'merchant/views/GCMS/Resellers';
import Orders from 'merchant/views/GCMS/Orders';
import Funds from 'merchant/views/GCMS/Funds';
import Reports from 'merchant/views/GCMS/Reports';
import { RouteGuard } from 'merchant/components/ShowWhen';
import { GCMS_PATHS } from 'merchant/views/GCMS/shared/constants';
import ResellerDetails from './Resellers/ResellerDetails';
import BatchActions from 'merchant/views/GCMS/BatchActions';
import OrderDetails from 'merchant/views/GCMS/Orders/OrderDetails';
import OrderCreate from './Orders/OrderCreate';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: false,
      staleTime: 1000 * 60 * 60, // Consider data stale if older than an hour
      refetchOnWindowFocus: false,
    },
  },
});
function getTabs(selectedTab: (typeof GCMS_PATHS)[keyof typeof GCMS_PATHS]) {
  return TABS.map((tab) => (
    <TabItem value={tab.to}>
      <Text
        weight="semibold"
        color={
          tab.to === selectedTab ? 'surface.text.primary.normal' : 'interactive.text.gray.muted'
        }
      >
        {tab.title}
      </Text>
    </TabItem>
  ));
}

function getInitialSelectedTab(location) {
  const pathsList = Object.values(GCMS_PATHS);

  for (let i = 0; i < pathsList.length; i++) {
    const regex = new RegExp(pathsList[i] + '*');
    if (regex.test(location.pathname)) {
      return pathsList[i];
    }
  }

  return GCMS_PATHS.PROGRAMS;
}

const TABS = [
  {
    title: 'Batch Actions',
    to: GCMS_PATHS.BATCH_ACTIONS,
    element: BatchActions,
  },
  {
    title: 'Programs',
    to: GCMS_PATHS.PROGRAMS,
    element: Programs,
  },
  {
    title: 'Resellers',
    to: GCMS_PATHS.RESELLERS,
    element: Resellers,
  },
  {
    title: 'Orders',
    to: GCMS_PATHS.ORDERS,
    element: Orders,
  },
  {
    title: 'Funds',
    to: GCMS_PATHS.FUNDS,
    element: Funds,
  },
  {
    title: 'Reports',
    to: GCMS_PATHS.REPORTS,
    element: Reports,
  },
];

const GCMSContainer = (): JSX.Element => {
  const location = useLocation();
  const navigate = useNavigate();
  const [selectedTab, setSelectedTab] = useState(getInitialSelectedTab(location));
  // const splitz = useSplitzService();
  function changeTab(value) {
    setSelectedTab(value);
    navigate(value);
  }

  useEffect(() => {
    setSelectedTab(getInitialSelectedTab(location));
  }, [location.pathname]);

  function getTabData() {
    return (
      <Routes>
        {TABS.map((tab) => (
          <Route
            path={`${tab.to.replace('/gcms/', '')}/*`}
            element={
              <RouteGuard>
                <tab.element />
              </RouteGuard>
            }
          />
        ))}
        <Route path="*" element={<Navigate to={GCMS_PATHS.PROGRAMS} replace />} />
      </Routes>
    );
  }

  return (
    <QueryClientProvider client={queryClient}>
      <Box backgroundColor="transparent" paddingX="24px" paddingY="20px" width="100%">
        <Box
          backgroundColor={'surface.background.gray.intense'}
          padding="20px"
          paddingTop="0px"
          borderRadius={'medium'}
          width={'100%'}
          height={'100%'}
        >
          <Routes>
            <Route path="programs/:programId" element={<ProgramPage />} />
            <Route path="resellers/:resellerId/*" element={<ResellerDetails />} />
            <Route path="orders/create/*" element={<OrderCreate />} />
            <Route path="orders/:orderId/*" element={<OrderDetails />} />
            <Route
              path="*"
              element={
                <Box>
                  <Tabs orientation="horizontal" value={selectedTab} onChange={changeTab}>
                    <TabList>{getTabs(selectedTab)}</TabList>
                  </Tabs>
                  {getTabData()}
                </Box>
              }
            />
          </Routes>
        </Box>
      </Box>
    </QueryClientProvider>
  );
};

export default connect((state) => ({
  mode: state.session?.mode,
  merchant_id: state.session?.user?.current,
}))(GCMSContainer);
