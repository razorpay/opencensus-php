import React from 'react';

import { FormContextProvider } from './ProfileSettings/ShippingMethods/FormContext';
import ShippingEngineWrapper from './container';
import { ShippingSettingsRouteContextProvider } from './context/RouteContext';

const ShippingSettings = (): JSX.Element => {
  return (
    <ShippingSettingsRouteContextProvider>
      <FormContextProvider>
        <ShippingEngineWrapper />
      </FormContextProvider>
    </ShippingSettingsRouteContextProvider>
  );
};

export default ShippingSettings;
