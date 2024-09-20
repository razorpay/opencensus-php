import React from 'react';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { useSplitzService } from 'common/splitz';
import { isExperimentActive } from 'common/utils/rzp-utils';
import CheckoutTheme from 'merchant/views/Settings/Configuration/CheckoutTheme';

const CheckoutStylingV2 = React.lazy(
  () => import(/* webpackChunkName: "CheckoutStylingV2" */ './CheckoutStyling'),
);

const CheckoutStylingExperiment = (props) => {
  const {
    abExperiments: { enableCheckoutV2Configuration },
  } = useSplitzService();

  const isCheckoutV2Enabled = isExperimentActive(enableCheckoutV2Configuration);

  if (isCheckoutV2Enabled) {
    return (
      <SuspenseWithLoader>
        <CheckoutStylingV2 />
      </SuspenseWithLoader>
    );
  }

  return <CheckoutTheme {...props} />;
};

export default CheckoutStylingExperiment;
