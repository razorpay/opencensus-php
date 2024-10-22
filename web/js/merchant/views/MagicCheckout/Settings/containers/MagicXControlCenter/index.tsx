import React from 'react';
import { connect } from 'react-redux';

import ErrorBoundary, { Ranks, Teams } from 'common/new-ui/ErrorBoundary';
import { TabContent } from 'merchant/views/MagicCheckout/Settings/containers/MagicXControlCenter/styled';
import OnboardedMerchantTab from 'merchant/views/MagicCheckout/Settings/containers/MagicXControlCenter/OnboardedMerchantTab';
import WelcomeMerchantTab from 'merchant/views/MagicCheckout/Settings/containers/MagicXControlCenter/WelcomeMerchantTab';

export const ControlCenter = ({ user }) => {
  const { isC360OnboardingCompleted } = user;
  return (
    <ErrorBoundary team={Teams?.MAGIC_CHECKOUT} rank={Ranks.P0} resetOnProps>
      <TabContent>
        {isC360OnboardingCompleted ? <OnboardedMerchantTab /> : <WelcomeMerchantTab />}
      </TabContent>
    </ErrorBoundary>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps)(ControlCenter);
