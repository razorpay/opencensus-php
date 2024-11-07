import React from 'react';
import { connect } from 'react-redux';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import VerticalNavContainer from 'merchant/views/MagicCheckout/common/components/VerticalNavContainer';

import {
  DEFAULT_ROUTES as NAV_ITEMS,
  PATH_PREFIX,
} from 'merchant/views/MagicCheckout/MagicDashboard/routes';

const MagicDashboard = ({ magicCheckout }) => {
  const {
    cod_intelligence: isCODIntelligenceEnabled,
    cod_order_control: isCODOrderControlEnabled,
  } = magicCheckout;

  const customRouteCheck = (item, user) => {
    if (
      item.label === 'Key Reports and Analytics' &&
      !isCODIntelligenceEnabled &&
      (!user?.isMagicRTOAnalyticsV3Enabled || !isCODOrderControlEnabled) &&
      !user.isMagicOrderAnalyticsEnabled
    ) {
      return false;
    }
    return true;
  };

  //Common Component to render L2 Navigation
  return (
    <SuspenseWithLoader type="center">
      <VerticalNavContainer
        navItems={NAV_ITEMS}
        basePath={PATH_PREFIX}
        customRouteCheck={customRouteCheck}
      />
    </SuspenseWithLoader>
  );
};

const mapStateToProps = (state) => ({
  magicCheckout: state.magicCheckout,
});

export default connect(mapStateToProps, null)(MagicDashboard);
