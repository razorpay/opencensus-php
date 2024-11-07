import { useEffect, useState } from 'react';
import { NavLink, Navigate, Route, Routes } from 'react-router-dom';

import { withRouter } from 'common/deprecated/withRouter';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import Spinner from 'common/ui/Spinner';

import { useSplitzService } from 'common/splitz';
import { RouteGuard } from 'merchant/components/ShowWhen';
import {
  checkMagicConfigurationFlow,
  convertMagicRoutesToConfigurationFlow,
} from 'merchant/views/MagicCheckout/utils/Configuration';

import magicCheckoutRoutesV1 from 'merchant/views/MagicCheckout/MagicCheckoutRoutes';
import magicCheckoutRoutesV2 from 'merchant/views/MagicCheckout/MagicCheckoutRoutesV2';
import { PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';
import { ACCESS_ROLES } from 'merchant/views/MagicCheckout/Settings/constants';
import { RCOD_APP_NAME, SOPC_APP_NAME } from 'merchant/views/MagicCheckout/common/constants';
import {
  MAGIC_DASHBOARD_REVAMP_EXPERIMENT,
  MAGICX_PUBLICAPP_COD_EXPERIMENT,
} from 'merchant/views/MagicCheckout/constants';

import { useMagicExperiment } from 'merchant/views/MagicCheckout/utils/useMagicExperiment';
import { isRouteAuthorised } from 'merchant/views/MagicCheckout/utils/genericRouteCheck';

const getTabName = (tabName, dashboardView, abExperiments) => {
  if (
    tabName === 'Order Analytics' &&
    (dashboardView === SOPC_APP_NAME || dashboardView === RCOD_APP_NAME)
  ) {
    return 'Analytics';
  }
  if (
    tabName === 'Magic Dashboard' &&
    (dashboardView === SOPC_APP_NAME || dashboardView === RCOD_APP_NAME) &&
    abExperiments?.[MAGICX_PUBLICAPP_COD_EXPERIMENT]?.variables?.result === 'on'
  ) {
    return 'Checkout360';
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
  /**
   * configFlag represents if we are on magic configuration flow which will render Magic Checkout on new route.
   * If this flag is true , we will render Tabs with updated paths that supports configuration flow.
   */
  const configFlag = checkMagicConfigurationFlow();
  const { abExperiments } = useSplitzService();
  const isMagicDashboardV2Enabled = useMagicExperiment(MAGIC_DASHBOARD_REVAMP_EXPERIMENT);
  const [magicCheckoutRoutes, setMagicCheckoutRoutes] = useState();
  let redirectPath;

  useEffect(() => {
    let magicCheckoutRoutesTemp = isMagicDashboardV2Enabled
      ? magicCheckoutRoutesV2
      : magicCheckoutRoutesV1;
    if (configFlag)
      magicCheckoutRoutesTemp = convertMagicRoutesToConfigurationFlow(magicCheckoutRoutesTemp);
    setMagicCheckoutRoutes(magicCheckoutRoutesTemp);
  }, [isMagicDashboardV2Enabled]);

  /**
   * Orders Tab has 3 sub-tabs and checks for atleast one of the sub-tab should be true for the Orders Tab
   * to be rendered
   */
  const shouldEnableOrdersTab = () => {
    const isCODOrderConversionEnabled =
      platform !== 'native' && isPrepayCODEnabled && !isRcodEnabled;
    const isEditOrders =
      platform === PLATFORMS.VALUES.SHOPIFY &&
      user.isMagicShopifyOrderEditEnabled &&
      !isRcodEnabled;
    return isCODOrderControlEnabled || isCODOrderConversionEnabled || isEditOrders;
  };

  const customRouteCheck = (item) => {
    if (item.condition && !item.condition(user, abExperiments, platform, dashboardView))
      return null;

    if (
      item.tabName === 'RTO Analytics' &&
      !isCODIntelligenceEnabled &&
      (!user.isMagicRTOAnalyticsV3Enabled || !isCODOrderControlEnabled)
    )
      return null;

    /**
     * Checks for Dashboard Revamp(V2) Routes
     */
    if (
      item.tabName === 'Reports & Analytics' &&
      ((isRcodEnabled && !user.isMagicOrderAnalyticsEnabled) ||
        (!isCODIntelligenceEnabled &&
          (!user?.isMagicRTOAnalyticsV3Enabled || !isCODOrderControlEnabled) &&
          !user.isMagicOrderAnalyticsEnabled))
    )
      return null;

    if (item.tabName === 'Orders') return shouldEnableOrdersTab();

    /**
     * Checks for Original(V1) Routes
     */
    if (item.tabName === 'COD Orders' && !isCODOrderControlEnabled) return null;
    if (item.tabName === 'COD Order Conversion' && (platform === 'native' || !isPrepayCODEnabled))
      return null;

    /**
     * Common Checks for V1 & V2 Routes
     */
    if (
      (item.tabName === 'Edit Orders' || item.tabName === 'Coupons') &&
      platform !== PLATFORMS.VALUES.SHOPIFY
    )
      return null;
    if (
      (item.tabName === 'Settings' || item.tabName === 'Setup & Settings') &&
      !ACCESS_ROLES.includes(user.role) &&
      !isCODOrderControlEnabled
    )
      return null;
    return true;
  };

  const routeCheck = (item) => {
    if (!isRouteAuthorised(item, user, abExperiments, isRcodEnabled, platform)) return null;

    if (!customRouteCheck(item)) return null;
    return true;
  };

  const renderNav = (item) => {
    if (!routeCheck(item)) return null;
    if (!redirectPath) {
      redirectPath = item.path;
    }

    return (
      <NavLink key={item.path} to={item.path}>
        {getTabName(item.tabName, dashboardView, abExperiments)}
      </NavLink>
    );
  };
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
              {magicCheckoutRoutes?.map(renderNav)}
            </header>
            <content>
              <Routes>
                {magicCheckoutRoutes?.map((item) => {
                  const path = configFlag
                    ? `${item?.path}/*`
                    : `${item.path.replace('/magic/', '')}/*`;

                  return (
                    <Route
                      key={item.path}
                      path={path}
                      element={
                        <RouteGuard additionalCondition={(_user) => routeCheck(item)}>
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
