import lazy from 'merchant/routes/LazyLoader';

import ShipRocketWrapper from 'merchant/views/MagicCheckout/Settings/containers/RTOReductionSetupV2/ShipRocketWrapper';

import { RoutesConfig } from 'merchant/views/MagicCheckout/types';

const PLATFORMS = {
  SHOPIFY: 'shopify',
  WOOCOMMERCE: 'woocommerce',
  NATIVE: 'native',
};

export const ACCESS_ROLES = ['owner', 'admin'];

const AllowList = lazy(
  () =>
    import(
      /* webpackChunkName: "AllowList" */ 'merchant/views/MagicCheckout/MagicIntelligence/containers/AllowList'
    ),
);

const BlockList = lazy(
  () =>
    import(
      /* webpackChunkName: "BlockList" */ 'merchant/views/MagicCheckout/MagicIntelligence/containers/BlockList'
    ),
);

const RTOReduction = lazy(
  () =>
    import(
      /* webpackChunkName: "RTOReduction" */ 'merchant/views/MagicCheckout/Settings/containers/MagicIntelligenceTab'
    ),
);

const RTOHistory = lazy(
  () =>
    import(
      /* webpackChunkName: "RTOHistory" */ 'merchant/views/MagicCheckout/OrderStatusUpload/rtoHistoryUpload'
    ),
);

export const RTO_REDUCTION_ROUTES: RoutesConfig = {
  [PLATFORMS.NATIVE]: [
    {
      label: 'RTO Reduction',
      id: 'delivery-tracking',
      path: '/magic/setup-settings/rto-reduction-setup/rto-reduction',
      Component: RTOReduction,
    },
    {
      label: 'Delivery Tracking',
      id: 'delivery-tracking',
      path: '/magic/setup-settings/rto-reduction-setup/delivery-tracking',
      onRCOD: true,
      Component: ShipRocketWrapper,
    },
    {
      label: 'Allow List',
      id: 'allowlist',
      path: '/magic/setup-settings/rto-reduction-setup/allow-list',
      condition: (_user) => ACCESS_ROLES?.includes(_user?.role as string),
      Component: AllowList,
    },
    {
      label: 'Block List',
      id: 'blocklist',
      path: '/magic/setup-settings/rto-reduction-setup/block-list',
      condition: (_user) => ACCESS_ROLES?.includes(_user?.role as string),
      Component: BlockList,
      onRCOD: true,
    },
    {
      label: 'RTO History',
      path: '/magic/setup-settings/rto-reduction-setup/rto-history',
      condition: (_user) => true,
      Component: RTOHistory,
      onRCOD: true,
    },
  ],
  [PLATFORMS.SHOPIFY]: [
    {
      label: 'RTO Reduction',
      id: 'delivery-tracking',
      path: '/magic/setup-settings/rto-reduction-setup/rto-reduction',
      Component: RTOReduction,
      onRCOD: true,
    },
    {
      label: 'Delivery Tracking',
      id: 'delivery-tracking',
      path: '/magic/setup-settings/rto-reduction-setup/delivery-tracking',
      Component: ShipRocketWrapper,
      className: 'shipping-service',
      onRCOD: true,
    },
    {
      label: 'Allow List',
      id: 'allowlist',
      path: '/magic/setup-settings/rto-reduction-setup/allow-list',
      condition: (_user) => ACCESS_ROLES?.includes(_user?.role as string),
      Component: AllowList,
    },
    {
      label: 'Block List',
      id: 'blocklist',
      path: '/magic/setup-settings/rto-reduction-setup/block-list',
      condition: (_user) => ACCESS_ROLES?.includes(_user?.role as string),
      Component: BlockList,
      onRCOD: true,
    },
    {
      label: 'RTO History',
      path: '/magic/setup-settings/rto-reduction-setup/rto-history',
      condition: (_user) => true,
      Component: RTOHistory,
      onRCOD: true,
    },
  ],
  [PLATFORMS.WOOCOMMERCE]: [
    {
      label: 'RTO Reduction',
      id: 'delivery-tracking',
      path: '/magic/setup-settings/rto-reduction-setup/rto-reduction',
      Component: RTOReduction,
    },
    {
      label: 'Allow List',
      id: 'allowlist',
      path: '/magic/setup-settings/rto-reduction-setup/allow-list',
      condition: (_user) => ACCESS_ROLES?.includes(_user?.role as string),
      Component: AllowList,
    },
    {
      label: 'Block List',
      id: 'blocklist',
      path: '/magic/setup-settings/rto-reduction-setup/block-list',
      condition: (_user) => ACCESS_ROLES?.includes(_user?.role as string),
      Component: BlockList,
      onRCOD: true,
    },
    {
      label: 'RTO History',
      path: '/magic/setup-settings/rto-reduction-setup/rto-history',
      condition: (_user) => true,
      Component: RTOHistory,
      onRCOD: true,
    },
  ],
};
