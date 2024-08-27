import React, { useState } from 'react';
import { connect } from 'react-redux';

import SamedayUpselling from './Modals/ScheduledModal/components/Upselling';
import { SAMEDAY_MODAL_LOCATIONS } from './Modals/ScheduledModal/constants';
import { VIEWS } from './constants';

const UpsellBanners = (props) => {
  const {
    user: { isAutomaticSettlementEnabled, isAutomaticSettlementRestricted },
  } = props;

  const [view] = useState(() => {
    return !isAutomaticSettlementEnabled && !isAutomaticSettlementRestricted
      ? VIEWS.SAMEDAY_ELIGIBLE
      : null;
  });

  const renderContent = () => {
    switch (view) {
      case VIEWS.SAMEDAY_ELIGIBLE:
        return <SamedayUpselling showDiscount from={SAMEDAY_MODAL_LOCATIONS.ONDEMAND} />;

      default:
        return null;
    }
  };

  return renderContent();
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps)(UpsellBanners);
