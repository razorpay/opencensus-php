import React, { Suspense, lazy } from 'react';
import { useConnectedNavigationStore } from '@federated/apps/shell/connected-navigation/connectedNavigationStore';
import { DashboardLoader } from '@libs/shared-ui';
import { WorkspaceWrapper } from '@apps/shell/src/client/components/ShellLayout/WorkspaceWrapper';

const GrowthPage = lazy(() => import('@apps/shell/src/client/components/Navigation/GrowthPage'));
const AccessDeniedPage = lazy(
  () => import('@apps/shell/src/client/components/Navigation/AccessDenied'),
);

const componentMap = {
  access_denied_page: () => (
    <WorkspaceWrapper isFullPage={true}>
      <Suspense
        fallback={
          <DashboardLoader
            labelPosition="bottom"
            label="Loading... Please wait..."
            loaderType="wrt-viewport"
          />
        }
      >
        <AccessDeniedPage />
      </Suspense>
    </WorkspaceWrapper>
  ),
  growth_page: () => (
    <WorkspaceWrapper isFullPage={true}>
      <Suspense
        fallback={
          <DashboardLoader
            labelPosition="bottom"
            label="Loading... Please wait..."
            loaderType="wrt-viewport"
          />
        }
      >
        <GrowthPage />
      </Suspense>
    </WorkspaceWrapper>
  ),
};

type ComponentMapKeys = keyof typeof componentMap;

const withNavigationType = (DashboardEntry: React.ComponentType) => {
  return function ResolvedComponent(props: any) {
    const { products } = useConnectedNavigationStore();

    const product = (products as any)?.selectedProduct || {};
    const type = product?.selectAction?.actionType;

    if (!type) {
      return (
        <DashboardLoader
          labelPosition="bottom"
          label="Loading... Please wait..."
          loaderType="wrt-viewport"
        />
      );
    }

    const Component = componentMap[type] || DashboardEntry;
    
    return <Component {...props} />;
  };
};

export default withNavigationType;
