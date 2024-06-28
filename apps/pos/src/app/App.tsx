import * as React from 'react';
import styled from 'styled-components';
import { Outlet, Routes, Route } from 'react-router-dom';
import errorService from '@razorpay/universe-cli/errorService';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
// import { graphqlRequest } from '@dashboard/shared-utils/graphql/graphql';
// import { useInfiniteQuery } from '@tanstack/react-query';
// import { SALES_ONBOARDED_MERCHANTS } from '../services/queries/SalesDashboard';
import { MODULE_NAME, module_routes } from './utils/constants';
import POSDashboard from './views/POSDashboard';

// Some styles are applied globally, hence disabled them in POS module's root level
const StyledDashboardWrapper = styled.div`
  width: 100%;
  button {
    box-shadow: none;
    margin: 0px;
  }
  th,
  td {
    div {
      height: 100%;
    }
    padding: 0px;
  }
`;

const AssistedOnboarding: React.FC<{}> = () => {
  // const { data } = useInfiniteQuery({
  //   queryKey: ['TestKey'],
  //   queryFn: async () => {
  //     const response = await graphqlRequest({
  //       document: SALES_ONBOARDED_MERCHANTS,
  //       variables: {
  //         limit: 10,
  //         offset: 0,
  //         startDate: 1717547425,
  //         endDate: 1717979425,
  //         status: 'all',
  //       },
  //     });

  //     if (response?.salesOnboardedMerchants?.merchants) return response;
  //   },
  //   staleTime: 60000 * 1,
  //   retry: false,
  //   networkMode: 'always',
  //   refetchOnWindowFocus: false,
  //   refetchOnMount: false,
  //   keepPreviousData: true,
  // });
  // console.log('DATA', data);

  return (
    <ErrorBoundary rank={errorService.ErrorRank.P0} tags={{ module: MODULE_NAME }}>
      <StyledDashboardWrapper>
        <Routes>
          <Route path={module_routes.dashboard.root} element={<POSDashboard />} />
          {/* <Route
            path={`${module_routes.merchants_onboarding.root}${
              module_routes.merchants_onboarding.nested_routes ? '/*' : ''
            }`}
            element={<MerchantsOnboarding />}
          />
          <Route
            path={`${module_routes.devices.root}${module_routes.devices.nested_routes ? '/*' : ''}`}
            element={<DeviceSelectionAndOrder />}
          /> */}
          {/* add micro-app routes here */}
        </Routes>
        <Outlet />
      </StyledDashboardWrapper>
    </ErrorBoundary>
  );
};

export default AssistedOnboarding;
