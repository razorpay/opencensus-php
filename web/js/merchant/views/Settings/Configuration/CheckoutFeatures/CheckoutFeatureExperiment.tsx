import React from 'react';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

const CheckoutFeatures = React.lazy(
  () => import(/* webpackChunkName: "CheckoutFeatures" */ './CheckoutFeatures'),
);

const CheckoutConfigExperiment = (props) => {
  return (
    <SuspenseWithLoader>
      <CheckoutFeatures extraConfig={props.extraConfig} />
    </SuspenseWithLoader>
  );
};

export default CheckoutConfigExperiment;
