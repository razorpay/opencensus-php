import React from 'react';
import { BrandNameLabel, BrandNameValue } from '.';
import { connect } from 'react-redux';
import IntoView from 'common/ui/IntoView';
import {
  BILLING_LABEL,
  UPDATE_BILLING_LABEL,
  ACTION_QUERY_PARAM_KEY,
} from 'merchant/views/Account/Profile/deeplink-constants';
import DetailRow from 'merchant/components/DetailRow';
import TriggerOnQueryParamMatch from 'common/ui/TriggerOnQueryParamMatch';
import { Box } from '@razorpay/blade/components';
import {
  INDIVIDUAL,
  NOT_REGISTERED,
} from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';

const BrandName = ({ user }): JSX.Element | null => {
  const shouldShowBrandName: boolean =
    user.activation_status == 'activated' &&
    user.business_type != INDIVIDUAL &&
    user.business_type != NOT_REGISTERED &&
    user.isAdminOrOwner;

  if (!shouldShowBrandName) {
    return null;
  }

  return (
    <TriggerOnQueryParamMatch
      queryParamsMapping={[
        {
          key: ACTION_QUERY_PARAM_KEY,
          value: UPDATE_BILLING_LABEL,
          trigger: shouldShowBrandName,
        },
      ]}
    >
      <IntoView hashedWith={BILLING_LABEL}>
        {user.isAccountAndSettingsRevampEnabled ? (
          <Box display="flex" flexDirection="column" marginTop="spacing.4" marginBottom="spacing.3">
            <label className="col-md-12 col-sm-12">
              <strong>
                <BrandNameLabel />
              </strong>
            </label>
            <div className="col-md-5 col-sm-6">
              <BrandNameValue />
            </div>
          </Box>
        ) : (
          <DetailRow label={() => <BrandNameLabel />} value={() => <BrandNameValue />} />
        )}
      </IntoView>
    </TriggerOnQueryParamMatch>
  );
};

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps)(BrandName);
