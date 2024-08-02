import React from 'react';
import { Box, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';

import IntoView from 'common/ui/IntoView';
import TriggerOnQueryParamMatch from 'common/ui/TriggerOnQueryParamMatch';
import DetailRow from 'merchant/components/DetailRow';
import {
  BILLING_LABEL,
  UPDATE_BILLING_LABEL,
  ACTION_QUERY_PARAM_KEY,
} from 'merchant/views/Account/Profile/deeplink-constants';
import {
  INDIVIDUAL,
  NOT_REGISTERED,
} from 'merchant/views/onboarding/mobile/Constants/OnboardingConstants';

import { BrandNameLabel, BrandNameValue } from '.';

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
            <label>
              <Text weight="semibold" color="surface.text.gray.subtle">
                <BrandNameLabel />
              </Text>
            </label>
            <div>
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
