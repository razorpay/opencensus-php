import React from 'react';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

import { routes } from './constants';
import { useShippingSettingsRouteContext } from './context/RouteContext';
import { Wrapper } from './styles';

const ShippingEngineWrapper = (): JSX.Element => {
  const { activeRoute } = useShippingSettingsRouteContext();
  const Component = routes[activeRoute];
  return (
    <SuspenseWithLoader type="center">
      <Wrapper>
        <Component />
      </Wrapper>
    </SuspenseWithLoader>
  );
};

export default ShippingEngineWrapper;
