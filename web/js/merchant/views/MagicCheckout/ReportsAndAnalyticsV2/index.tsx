import React from 'react';
import { connect } from 'react-redux';

import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import VerticalNavContainer from 'merchant/views/MagicCheckout/common/components/VerticalNavContainer';

import { DEFAULT_ROUTES as NAV_ITEMS } from 'merchant/views/MagicCheckout/ReportsAndAnalyticsV2/routes';

import { GenericRecord, User } from 'merchant/views/MagicCheckout/types';

import { SOPC_APP_NAME, RCOD_APP_NAME } from 'merchant/views/MagicCheckout/common/constants';
import { PATH_PREFIX } from 'merchant/views/MagicCheckout/ReportsAndAnalyticsV2/constants';

interface ReportsAndAnalyticsProps {
  magicCheckout: GenericRecord;
}

const ReportsAndAnalytics: React.FC<ReportsAndAnalyticsProps> = ({ magicCheckout }) => {
  const { dashboardView, cod_intelligence, cod_order_control } = magicCheckout;

  const customRouteCheck = (
    item,
    user: User,
    _abExperiments?: GenericRecord,
    _isRCOD?: boolean,
  ) => {
    if (
      item.label === 'Order Analytics' &&
      (dashboardView === SOPC_APP_NAME || dashboardView === RCOD_APP_NAME)
    ) {
      return false;
    }

    if (
      item.label === 'RTO Analytics' &&
      !cod_intelligence &&
      (!user?.isMagicRTOAnalyticsV3Enabled || !cod_order_control)
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

export default connect(mapStateToProps, null)(ReportsAndAnalytics);
