import React from 'react';
import { connect } from 'react-redux';

import { useSplitzService } from 'common/splitz';

import BusinessWebsiteDetails from './BusinessWebsiteDetails';
import BusinessWebsiteDetailsV2 from './v2';
import { shouldShowBusinessWebsiteV2 } from './utils';

const BusinessWebsiteDetailsEntry = ({
  user,
  isAccountAndSettingsRevampFlow = false,
  ...props
}) => {
  const {
    abExperiments: { business_website_v2_automation },
  } = useSplitzService();

  const shouldShowV2 = shouldShowBusinessWebsiteV2(business_website_v2_automation, user);

  return isAccountAndSettingsRevampFlow && shouldShowV2 ? (
    <BusinessWebsiteDetailsV2 />
  ) : (
    <BusinessWebsiteDetails {...props} />
  );
};

export default connect((state) => ({
  user: state.session.user,
}))(BusinessWebsiteDetailsEntry);
