import lazy from 'merchant/routes/LazyLoader';
import CODToPrepaidLinks from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks';

import { formatRoutesByPlatform } from 'merchant/views/MagicCheckout/utils/formatGenericRoutes';

import { RoutesConfig, User, RouteItem } from 'merchant/views/MagicCheckout/types';

import {
  COD_ORDERS,
  COD_ORDER_CONEVRSION,
  EDIT_ORDERS,
} from 'merchant/views/MagicCheckout/OrdersV2/constants';

const EditOrders = lazy(
  () =>
    import(
      /* webpackChunkName: 'MagicEditOrders' */
      'merchant/views/MagicCheckout/ShopifyOrderEditing'
    ),
);

const CODOrders = lazy(
  () =>
    import(
      /* webpackChunkName: "CODOrdersTab" */ 'merchant/views/MagicCheckout/OrdersV2/components/CODOrders'
    ),
);

const GENERIC_ROUTES: RouteItem[] = [
  {
    label: EDIT_ORDERS,
    path: '/magic/orders/edit-orders',
    Component: EditOrders,
    condition: (_user: User) => _user.isMagicShopifyOrderEditEnabled,
  },
  {
    label: COD_ORDERS,
    path: '/magic/orders/cod-orders',
    Component: CODOrders,
    onRCOD: true,
  },
  {
    label: COD_ORDER_CONEVRSION,
    path: '/magic/orders/order-conversion',
    Component: CODToPrepaidLinks,
    condition: (_user: User) => _user.isMagicPrepayCODEnabled,
  },
];

//DRY - Above routes are common for all platforms
export const DEFAULT_ROUTES: RoutesConfig = formatRoutesByPlatform(GENERIC_ROUTES);
