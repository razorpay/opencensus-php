import { useCallback } from 'react';
import { NavLink, Navigate, Route, Routes } from 'react-router-dom';

import { withRouter } from 'common/deprecated/withRouter';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { useSplitzService } from 'common/splitz';
import Spinner from 'common/ui/Spinner';
import { RouteGuard } from 'merchant/components/ShowWhen';
import magicCheckoutRoutes from 'merchant/views/MagicCheckout/MagicCheckoutRoutes';
import { PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';
import { ACCESS_ROLES } from 'merchant/views/MagicCheckout/Settings/constants';
import { RCOD_APP_NAME, SOPC_APP_NAME } from 'merchant/views/MagicCheckout/common/constants';
let redirectPath;

const getTabName = (tabName, dashboardView) => {
  if (
    tabName === 'Order Analytics' &&
    (dashboardView === SOPC_APP_NAME || dashboardView === RCOD_APP_NAME)
  ) {
    return 'Analytics';
  }
  return tabName;
};

const RouteContainer = ({
  user,
  isCODIntelligenceEnabled,
  isCODOrderControlEnabled,
  isPrepayCODEnabled,
  isRcodEnabled,
  platform,
  dashboardView,
}) => {
  const { abExperiments } = useSplitzService();

  const renderNav = useCallback(
    (item) => {
      if (
        item.tabName === 'RTO Analytics' &&
        !isCODIntelligenceEnabled &&
        (!user.isMagicRTOAnalyticsV3Enabled || !isCODOrderControlEnabled)
      )
        return null;
      if (item.tabName === 'COD Orders' && !isCODOrderControlEnabled) return null;
      if (item.tabName === 'COD Order Conversion' && (platform === 'native' || !isPrepayCODEnabled))
        return null;
      if (item.condition && !item.condition(user, abExperiments, platform, dashboardView))
        return null;
      if (item.tabName === 'Edit Orders' && platform !== PLATFORMS.VALUES.SHOPIFY) return null;
      if (
        item.tabName === 'Settings' &&
        !ACCESS_ROLES.includes(user.role) &&
        !isCODOrderControlEnabled
      )
        return null;

      if (!item.onRCOD && isRcodEnabled) {
        return null;
      }

      if (!redirectPath) {
        redirectPath = item.path;
      }
      return (
        <NavLink key={item.path} to={item.path}>
          {getTabName(item.tabName, dashboardView)}
        </NavLink>
      );
    },
    [
      redirectPath,
      user,
      isCODIntelligenceEnabled,
      isCODOrderControlEnabled,
      platform,
      abExperiments,
      isRcodEnabled,
      dashboardView,
    ],
  );

  return (
    <SuspenseWithLoader type="center">
      <tabbed-container>
        {isCODIntelligenceEnabled === null || isCODOrderControlEnabled === null ? (
          <div className="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <>
            <header id="super-checkout-header" className="scrollable-tab-header">
              {magicCheckoutRoutes.map(renderNav)}
            </header>
            <content>
              <Routes>
                {magicCheckoutRoutes.map((item) => {
                  return (
                    <Route
                      key={item.path}
                      path={`${item.path.replace('/magic/', '')}/*`}
                      element={
                        <RouteGuard
                          additionalCondition={(_user) =>
                            !item.condition || item.condition(_user, abExperiments, platform)
                          }
                        >
                          <item.Component />
                        </RouteGuard>
                      }
                    />
                  );
                })}
                <Route path="*" element={<Navigate to={redirectPath} replace />} />
              </Routes>
            </content>
          </>
        )}
      </tabbed-container>
    </SuspenseWithLoader>
  );
};
export default withRouter(RouteContainer);
