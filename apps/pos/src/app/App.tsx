import * as React from 'react';
import styled from 'styled-components';
import { Outlet, Routes, Route } from 'react-router-dom';
import errorService from '@razorpay/universe-cli/errorService';
import ErrorBoundary from '@razorpay/universe-cli/errorService/ErrorBoundary';
import POSDashboard from './views/POSDashboard';
import { MODULE_NAME, module_routes } from './utils/constants';

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
