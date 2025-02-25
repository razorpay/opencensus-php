import React from 'react';
import loadable from '@loadable/component';
import { isBrowser } from './server/utils';
import { DashboardLoader } from '@libs/shared-ui';

// Load only on client side. Instance of zustand store will be same.
const RazorpayDashboard = loadable(
  () => import('./client/components/RazorpayDashboard/RazorpayDashboard'),
  {
    ssr: false,
  },
);

const App = (): React.ReactElement => {
  return isBrowser() ? (
    <RazorpayDashboard />
  ) : (
    <DashboardLoader
      labelPosition="bottom"
      label="Initializing Shell..."
      accessibilityLabel="Shell Page Loader"
      loaderType="wrt-viewport"
    />
  );
};

export default App;
