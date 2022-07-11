import { connect } from 'react-redux';
import React from 'react';
import TwoFactorVerificaionContext from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';
import { toggleMerchant2FaEnforcement as toggleMerchant2FaEnforcementReducer } from 'merchant/reducers/team';
import { updateSession } from 'merchant/reducers/session';
import User from 'merchant/models/User';
import Toggle2FA from '../../components/TwoFAVerification/Toggle2FA';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';

class Merchant2FASettings extends React.PureComponent {
  static contextType = TwoFactorVerificaionContext;

  onToggleComplete = (twoFaEnabled) => {
    const user = new User({
      ...this.props.user,
    });
    user.secondFactorAuthOfCurrentMerchant = twoFaEnabled;

    this.props.updateSession({ user });
  };

  handleTwoFactorVerificationOnLoginToggle = (onToggleChange) => (flag, callback) => {
    selfServeTrackInitiate({
      selfServeAction: '2FA Verification Created',
      page: 'Team',
      screen: 'My Account',
    });
    analyticsTrack({
      objectName: '2fa team',
      actionName: 'toggled',
      screen: 'my account',
      properties: {
        type: flag ? 'enable' : 'disable',
        location: 'my screen',
        twoFactorVerified: window.rzp_user?.user?.two_fa_verified,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    return this.context.criticalFlow({
      mode: ['live', 'test'],
      // forward additional data from TwoFactorVerificationProvider to Toggle2FA
      onUserTwoFaVerified: (data) => {
        // Tempory implementation
        // to avoid requirement of both new and old context
        // In <Toggle2Fa/>
        return onToggleChange(flag, callback, data);
      },

      onFlowTermination: () => {
        return callback(false);
      },
    });
  };

  render() {
    const { current, merchants } = this.props.user;
    const { toggleMerchant2FaEnforcement } = this.props;
    const twoFaEnabled = merchants[current].second_factor_auth;
    return (
      <Toggle2FA
        renderDescription={Merchant2FADescription}
        renderTitle={Merchant2FATitle}
        toggle2FaEnforcement={toggleMerchant2FaEnforcement}
        twoFaEnabled={twoFaEnabled}
        onToggleComplete={this.onToggleComplete}
        getToggle2FaSuccessMsg={getToggle2FaSuccessMsg}
        eventPrefix="2fa team"
        confirmDisableMessage="Are you sure you want to disable 2-step verification to all your team members?"
        confirmEnableMessage="Are you sure you want to enable 2-step verification to all your team members?"
        location="manage team"
        onToggleChange={this.handleTwoFactorVerificationOnLoginToggle}
      />
    );
  }
}

function Merchant2FATitle() {
  return (
    <span class="title">
      <i class="i i-phonelink-lock" /> 2-Step verification to the team
    </span>
  );
}

function Merchant2FADescription() {
  return (
    <>
      <p>
        2-step verification will be enforced to all the team members who have access to this
        Dashboard.
      </p>
      <p>
        <strong>Note:</strong> This setting requires 2-step verification set up on your account
      </p>
    </>
  );
}

function getToggle2FaSuccessMsg(twoFaStatus) {
  return `2-step verification successfully turned ${twoFaStatus} for all your team members`;
}

const mapStateToProps = (state) => ({
  user: state.session.user,
});

export default connect(mapStateToProps, {
  toggleMerchant2FaEnforcement: toggleMerchant2FaEnforcementReducer,
  updateSession,
})(Merchant2FASettings);
