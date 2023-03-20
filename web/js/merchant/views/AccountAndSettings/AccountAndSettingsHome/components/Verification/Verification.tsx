import { Text } from '@razorpay/blade/components';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';
import User2FASettings from 'merchant/views/Account/Profile/components/User2FASettings';
import { TooltipContainer } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/UserInfo/styled';
import { VerificationPropsInterface } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import React from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { IconText, VerificationContainer } from './styled';

const Verification = ({ isMobile, user }: VerificationPropsInterface): JSX.Element | null => {
  const {
    user: { org_enforced_second_factor_auth, signup_via_email },
    is2FAMobileSignupEnabled,
  } = user;

  // condition to check whether merchant is enabled for 2fa verification
  const shouldShow2FASettings =
    !org_enforced_second_factor_auth &&
    (signup_via_email || is2FAMobileSignupEnabled) &&
    !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.TwoFactorVerification);

  if (!shouldShow2FASettings) {
    return null;
  }

  return (
    <VerificationContainer>
      <IconText>
        <Text type="subtle" weight="bold">
          2-step verification
        </Text>
        <TooltipContainer className="verification-tooltip">
          <i className="i i-info-tooltip" />
          <Popover theme="dark" align="top" horizontalAdjustment={isMobile ? 80 : 100}>
            <PopoverBody>
              <div>
                Secure your account by using a one-time verification code each time you log in.
              </div>
            </PopoverBody>
          </Popover>
        </TooltipContainer>
      </IconText>
      <User2FASettings shouldOnlyToggle />
    </VerificationContainer>
  );
};

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
  };
};

export default compose(connect(mapStateToProps, null))(Verification);
