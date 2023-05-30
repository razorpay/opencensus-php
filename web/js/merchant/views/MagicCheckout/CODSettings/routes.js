import lazy from 'merchant/routes/LazyLoader';
import Blocklist from 'merchant/views/MagicCheckout/MagicIntelligence/containers/BlockList';

const CODEngine = lazy(() =>
  import(
    /* webpackChunkName: "CODSettings" */ 'merchant/views/MagicCheckout/CODSettings/containers/CODEngine'
  ),
);

const routes = [
  {
    title: 'COD Engine',
    id: 'cod-engine',
    component: <CODEngine />,
    className: 'cod-list-container',
  },
  {
    title: 'Block List',
    id: 'blocklist',
    component: <Blocklist />,
    className: 'cod-blocklist-container',
  },
];

export default routes;
