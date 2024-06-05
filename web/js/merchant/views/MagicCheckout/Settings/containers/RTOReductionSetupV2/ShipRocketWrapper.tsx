import React from 'react';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

import lazy from 'merchant/routes/LazyLoader';

import { SHIPPING_PARTNERS } from 'merchant/views/MagicCheckout/ShippingServices/constants';

const DeliveryTrackingShipRocketSettings = lazy(
  () =>
    import(
      /* webpackChunkName: "DeliveryTrackingShipRocketSettings" */ 'merchant/views/MagicCheckout/ShippingServices'
    ),
);
const ShipRocketWrapper: React.FC = () => {
  return (
    <SuspenseWithLoader type="center">
      <DeliveryTrackingShipRocketSettings
        providers={Object.keys(SHIPPING_PARTNERS)}
        magicIntelligence
      />
    </SuspenseWithLoader>
  );
};

export default ShipRocketWrapper;
