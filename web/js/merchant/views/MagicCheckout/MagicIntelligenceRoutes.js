import ShipRocketSettings from 'merchant/views/MagicCheckout/ShippingServices';
import BlockList from 'merchant/views/MagicCheckout/MagicIntelligence/containers/BlockList';
import AllowList from 'merchant/views/MagicCheckout/MagicIntelligence/containers/AllowList';
import { SHIPPING_PARTNERS } from 'merchant/views/MagicCheckout/ShippingServices/constants';

const routes = [
  {
    title: 'Delivery Tracking',
    id: 'delivery-tracking',
    component: <ShipRocketSettings providers={Object.keys(SHIPPING_PARTNERS)} magicIntelligence />,
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
