import ShipRocketSettings from 'merchant/views/MagicCheckout/ShippingServices';
import BlockList from 'merchant/views/MagicCheckout/MagicIntelligence/containers/BlockList';
import AllowList from 'merchant/views/MagicCheckout/MagicIntelligence/containers/AllowList';

const routes = [
  {
    title: 'Delivery Tracking',
    id: 'delivery-tracking',
    component: <ShipRocketSettings />,
    className: 'shipping-service',
  },
  {
    title: 'Blocklist',
    id: 'blocklist',
    component: <BlockList />,
    className: 'list-container',
  },
  {
    title: 'Allowlist',
    id: 'allowlist',
    component: <AllowList />,
    className: 'list-container',
  },
];

export default routes;
