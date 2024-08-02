import React from 'react';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { useSplitzService } from 'common/splitz';
import { isExperimentActive } from 'common/utils/rzp-utils';
import CheckoutTheme from 'merchant/views/Settings/Configuration/CheckoutTheme';

const CheckoutConfigV2 = React.lazy(
  () => import(/* webpackChunkName: "CheckoutConfigV2" */ './CheckoutConfig'),
);

const CheckoutConfigExperiment = (props) => {
  const {
    abExperiments: { enableCheckoutV2Configuration },
  } = useSplitzService();

  const isCheckoutV2Enabled = isExperimentActive(enableCheckoutV2Configuration);

  if (isCheckoutV2Enabled) {
    return (
      <SuspenseWithLoader>
        <CheckoutConfigV2 />
      </SuspenseWithLoader>
    );
  }

  return <CheckoutTheme {...props} />;
};

export default CheckoutConfigExperiment;
