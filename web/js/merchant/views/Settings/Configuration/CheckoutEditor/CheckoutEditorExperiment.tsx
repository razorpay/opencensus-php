import React from 'react';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

const CheckoutEditor = React.lazy(
  () => import(/* webpackChunkName: "CheckoutEditor" */ './CheckoutEditor'),
);

const CheckoutConfigExperiment = (props) => {
  return (
    <SuspenseWithLoader>
      <CheckoutEditor
        showFeatures={props.showFeatures}
        showStyling={props.showStyling}
        extraConfig={props.extraConfig}
      />
    </SuspenseWithLoader>
  );
};

export default CheckoutConfigExperiment;
