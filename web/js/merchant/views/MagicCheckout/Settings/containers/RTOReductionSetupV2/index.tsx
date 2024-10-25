import React from 'react';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import NavContainer from 'merchant/views/MagicCheckout/common/components/NavContainer';

import { RTO_REDUCTION_ROUTES } from 'merchant/views/MagicCheckout/Settings/containers/RTOReductionSetupV2/routes';

////Entry file to RTO Reduction Setup tab of new dashboard UI
const RTOReductionSetup: React.FC = () => {
  const path = '/magic/settings/rto-reduction-setup';
  return (
    <SuspenseWithLoader type="center">
      <NavContainer navItems={RTO_REDUCTION_ROUTES} basePath={path} />
    </SuspenseWithLoader>
  );
};

export default RTOReductionSetup;
