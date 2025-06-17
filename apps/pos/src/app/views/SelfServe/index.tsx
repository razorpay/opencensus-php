import React from 'react';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';
import errorService from '@razorpay/universe-cli/errorService';
import { Route, Routes, Navigate, Outlet, useMatch } from 'react-router-dom';
import { useStore } from '@federated/apps/shell/commonStore';
import { ErrorBoundary } from '@libs/shared-ui';
import { DASHBOARD_PRIORITY_RANKS as Ranks, DASHBOARD_TEAMS as Teams } from '@libs/shared-types';
import { RazorpayUser as User } from '@libs/shared-types';
import { isProductionEnv } from '@libs/shared-utils';

import CartPanel from './Cart/CartPanel';
import Catalog from './Catalog';
import CommsBanner from './CommsBanner';
import DeviceStoreWrapper from './DeviceStoreWrapper';
import OrderDetails from './OrderDetails';
import OrderList from './OrderList';
import OrderSummary from './OrderSummary';
import ProductDescription from './ProductDescription';
import { PosDeviceStoreProvider } from './providers';
import { ScrollObserverProvider } from './utils/ScrollObserver';

try {
  // initialize analytics 2.0
  analytics.init_EXPERIMENTAL({
    destinations: {
      lumberjack: {
        key: window.LUMBERJACK_API_KEY,
        appName: 'onboarding-new-events',
        url: window.LUMBERJACK_API_URL,
      },
      segment: {
        key: window.SEGMENT_API_KEY,
        priority: 'high',
      },
    },
    locale: 'IN',
    mode: isProductionEnv() ? 'live' : 'debug',
  });
  analytics.identify_EXPERIMENTAL(window?.rzp_user?.current);
} catch (error: unknown) {
  // capture error on analytics initialization failure
  errorService.captureError(error, {
    tags: {
      team: Teams.OMNI_CHANNEL,
      module: 'analytics',
    },
    rank: Ranks.P2,
  });
}

const POS = (): JSX.Element => {
  const user = useStore((state) => state.session.user);
  const mode = useStore((state) => state.session.mode);
  const isOrderConfirmation = !!useMatch({ path: '/pos/order-status', end: false });
  const isOrderSummary = location.pathname.endsWith('/order-summary');

  const onDeviceShopTabClick = () => {
    if (isOrderSummary) {
      analytics.track_EXPERIMENTAL(SignUpEvents.linkClicked, {
        label: 'Device Shop',
        section: 'Pre-checkout',
        whatsAppUpdates: 'No',
        subSection: 'Pre-checkout',
        l1FunnelStage: 'Device Exploration',
        l2FunnelStage: 'Pre-checkout',
      });
    } else {
      analytics.track_EXPERIMENTAL(SignUpEvents.linkClicked, {
        label: 'Device Shop',
        section: 'POS Catalog',
        whatsAppUpdates: 'No',
        subSection: 'POS Catalog',
        l1FunnelStage: 'Device Exploration',
        l2FunnelStage: 'POS Catalog',
      });
    }
  };

  const onOrdersTabClick = () => {
    if (isOrderSummary) {
      analytics.track_EXPERIMENTAL(SignUpEvents.linkClicked, {
        label: 'Orders',
        section: 'Pre-checkout',
        subSection: 'Pre-checkout',
        whatsAppUpdates: 'No',
        l1FunnelStage: 'Device Exploration',
        l2FunnelStage: 'Pre-checkout',
      });
    } else {
      analytics.track_EXPERIMENTAL(SignUpEvents.linkClicked, {
        label: 'Orders',
        section: 'POS Catalog',
        subSection: 'POS Catalog',
        whatsAppUpdates: 'No',
        l1FunnelStage: 'Device Exploration',
        l2FunnelStage: 'POS Catalog',
      });
    }
  };

  return (
    <ErrorBoundary resetOnProps team={Teams.OMNI_CHANNEL} rank={Ranks.P0}>
      <ScrollObserverProvider>
        <PosDeviceStoreProvider user={user as User}>
          {!isOrderConfirmation ? <CommsBanner mode={mode} user={user as User} /> : null}
          <Routes>
            <Route
              element={
                <DeviceStoreWrapper
                  tabs={[
                    {
                      title: 'Device Shop',
                      url: '/pos/catalog',
                      isMatchStartsWith: true,
                      onTabClick: onDeviceShopTabClick,
                    },
                    {
                      title: 'Orders',
                      url: '/pos/orders',
                      isMatchStartsWith: true,
                      onTabClick: onOrdersTabClick,
                    },
                  ]}
                  extra={<CartPanel isHidden={isOrderSummary} />}
                  customHeaderRightClass="pos-right-nav"
                  isTabsRequired={!isOrderConfirmation}
                >
                  <Outlet />
                </DeviceStoreWrapper>
              }
            >
              <Route path="/catalog">
                <Route index element={<Catalog />} />
                <Route path=":productName" element={<ProductDescription />} />
                <Route path=":productName?/:order-summary" element={<OrderSummary />} />
              </Route>
              <Route path="/orders">
                <Route index element={<OrderList />} />
                <Route path=":orderId" element={<OrderDetails />} />
                <Route path="*" element={<Navigate to="/pos/orders" replace />} />
              </Route>
              <Route path="/order-status/:orderId" element={<OrderDetails isOrderConfirmation />} />
              <Route path="*" element={<Navigate to="/pos/catalog" replace />} />
            </Route>
          </Routes>
        </PosDeviceStoreProvider>
      </ScrollObserverProvider>
    </ErrorBoundary>
  );
};

export default POS;
