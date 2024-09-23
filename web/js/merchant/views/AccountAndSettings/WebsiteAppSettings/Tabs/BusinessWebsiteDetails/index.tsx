import React from 'react';
import { connect } from 'react-redux';

import { useSplitzService } from 'common/splitz';
import { isExperimentActive } from 'common/utils/rzp-utils';

import BusinessWebsiteDetails from './BusinessWebsiteDetails';
import BusinessWebsiteDetailsV2 from './v2';

const BusinessWebsiteDetailsEntry = ({
  user,
  isAccountAndSettingsRevampFlow = false,
  ...props
}) => {
  const {
    abExperiments: { business_website_v2_automation },
  } = useSplitzService();
  const isV2ExperimentOn = isExperimentActive(business_website_v2_automation);
  const isUserEligibleForV2 =
    isAccountAndSettingsRevampFlow && user.isOrgRZP && user.isCountryIndia;

  const shouldShowV2 = isV2ExperimentOn && isUserEligibleForV2;

  return shouldShowV2 ? <BusinessWebsiteDetailsV2 /> : <BusinessWebsiteDetails {...props} />;
};

export default connect((state) => ({
  user: state.session.user,
}))(BusinessWebsiteDetailsEntry);
