import React from 'react';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import NavContainer from 'merchant/views/MagicCheckout/common/components/NavContainer';

import { COD_ROUTES } from 'merchant/views/MagicCheckout/Settings/containers/CODV2/routes';

//Entry file to COD tab of new dashboard UI
const COD: React.FC = () => {
  const path = '/magic/settings/cod-settings/';
  return (
    <SuspenseWithLoader type="center">
      <NavContainer navItems={COD_ROUTES} basePath={path} />
    </SuspenseWithLoader>
  );
};

export default COD;
