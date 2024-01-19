import lazy from 'merchant/routes/LazyLoader';
import Blocklist from 'merchant/views/MagicCheckout/MagicIntelligence/containers/BlockList';

const CODEngine = lazy(() =>
  import(
    /* webpackChunkName: "CODSettings" */ 'merchant/views/MagicCheckout/CODSettings/containers/CODEngine'
  ),
);

const Allowlist = lazy(() =>
  import(
    /*webpackChunkName: "Magic-COD-Engine-Allowlist" */ 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Allowlist'
  ),
);

const routes = [
  {
    title: 'Magic COD',
    rcodTitle: 'Smart COD',
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
  {
    title: 'Allow List',
    id: 'allowlist',
    component: <Allowlist />,
    className: 'cod-allowlist-container',
  },
];

export default routes;
