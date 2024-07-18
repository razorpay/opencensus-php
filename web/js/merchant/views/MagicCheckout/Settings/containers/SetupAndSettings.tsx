import React, { useMemo } from 'react';
import { connect } from 'react-redux';

import {
  ROUTES,
  DEFAULT_ROUTES,
  PLATFORMS,
  PATH_PREFIX,
} from 'merchant/views/MagicCheckout/Settings/constants';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import VerticalNavContainer from 'merchant/views/MagicCheckout/common/components/VerticalNavContainer';

import { RoutesConfig, GenericRecord } from 'merchant/views/MagicCheckout/types';

interface NestedVerticalTabProps {
  settings: GenericRecord;
  magicCheckout: GenericRecord;
}

const SetupAndSettings: React.FC<NestedVerticalTabProps> = ({ settings, magicCheckout }) => {
  const { platform, one_click_checkout = true } = settings;
  const { cod_order_control: isCODOrderControlEnabled } = magicCheckout;

  const NAV_ITEMS: RoutesConfig = useMemo(() => {
    return platform === PLATFORMS?.NATIVE || one_click_checkout
      ? { ...ROUTES }
      : { ...DEFAULT_ROUTES };
  }, [platform, one_click_checkout]);

  const customRouteCheck = (item, user) =>
    !(
      item.label === 'COD Review Workflow' &&
      (!isCODOrderControlEnabled || !user?.isMagicCODOrderAutomationEnabled)
    );

  //Common Component to render L2 Navigation
  return (
    <SuspenseWithLoader type="center">
      <VerticalNavContainer
        NAV_ITEMS={NAV_ITEMS}
        PATH_PREFIX={PATH_PREFIX}
        customRouteCheck={customRouteCheck}
      />
    </SuspenseWithLoader>
  );
};

const mapStateToProps = (state) => ({
  settings: state.magic_settings,
  magicCheckout: state.magicCheckout,
});

export default connect(mapStateToProps, null)(SetupAndSettings);
