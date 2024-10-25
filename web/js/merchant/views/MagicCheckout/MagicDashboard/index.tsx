import React from 'react';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import VerticalNavContainer from 'merchant/views/MagicCheckout/common/components/VerticalNavContainer';

import {
  DEFAULT_ROUTES as NAV_ITEMS,
  PATH_PREFIX,
} from 'merchant/views/MagicCheckout/MagicDashboard/routes';

const MagicDashboard: React.FC = () => {
  //Common Component to render L2 Navigation
  return (
    <SuspenseWithLoader type="center">
      <VerticalNavContainer navItems={NAV_ITEMS} basePath={PATH_PREFIX} />
    </SuspenseWithLoader>
  );
};

export default MagicDashboard;
