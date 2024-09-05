import lazy from 'merchant/routes/LazyLoader';

import { RoutesConfig, RouteItem } from 'merchant/views/MagicCheckout/types';

import { PLATFORMS } from 'merchant/views/MagicCheckout/constants';

const ReviewOrders = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicCODOrders" */ 'merchant/views/MagicCheckout/CODOrdersTab/tabs/ReviewOrdersTab'
    ),
);

const ApprovedOrders = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicCODOrders" */ 'merchant/views/MagicCheckout/CODOrdersTab/tabs/ApprovedOrdersTab'
    ),
);

const CanceledOrders = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicCODOrders" */ 'merchant/views/MagicCheckout/CODOrdersTab/tabs/CanceledOrdersTab'
    ),
);

const OnHoldOrders = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicCODOrders" */ 'merchant/views/MagicCheckout/CODOrdersTab/tabs/OnHoldOrdersTab'
    ),
);

const GENERIC_ROUTES: RouteItem[] = [
  {
    id: 'reviewOrdersTab',
    label: 'Review Orders',
    Component: ReviewOrders,
    path: '/magic/orders/cod-orders/review',
    onRCOD: true,
  },
  {
    id: 'approvedOrdersTab',
    label: 'Approved Orders',
    Component: ApprovedOrders,
    path: '/magic/orders/cod-orders/approved',
    onRCOD: true,
  },
  {
    id: 'canceledOrdersTab',
    label: 'Canceled Orders',
    Component: CanceledOrders,
    path: '/magic/orders/cod-orders/canceled',
    onRCOD: true,
  },
  {
    id: 'onHoldOrdersTab',
    label: 'On Hold Orders',
    Component: OnHoldOrders,
    path: '/magic/orders/cod-orders/on-hold',
    onRCOD: true,
  },
];

//DRY - Above routes are common for all platforms
export const DEFAULT_ROUTES: RoutesConfig = Object.values(PLATFORMS).reduce(
  (RTORoutes, Platform) => {
    RTORoutes[Platform] = GENERIC_ROUTES;
    return RTORoutes;
  },
  {},
);
