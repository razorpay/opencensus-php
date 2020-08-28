import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import TwoFactorVerificaionContext from 'common/ui/TwoFactorVerification/TwoFactorVerificationContext';

import { toggleUser2FaEnforcement } from 'merchant/reducers/team';
import { updateSession } from 'merchant/reducers/session';

import User from 'merchant/models/User';
import ShowWhen from 'merchant/components/ShowWhen';

import Toggle2FA from '../../components/TwoFAVerification/Toggle2FA';

@connect((state) => ({ user: state.session.user }), {
  toggleUser2FaEnforcement,
  updateSession,
})
export default class User2FASettings extends React.PureComponent {
  static contextType = TwoFactorVerificaionContext;

  onToggleComplete = (twoFaEnabled) => {
    const { user: currentUser } = this.props.user;
    const user = new User({
      ...this.props.user,
      user: {
        ...currentUser,
        second_factor_auth: twoFaEnabled,
      },
    });
    this.props.updateSession({ user });
  };

  handleTwoFactorVerificationOnLoginToggle = (onToggleChange) => (flag, callback) => {
    return this.context.criticalFlow({
      modes: ['live', 'test'],
      onUserTwoFaVerified: () => {
        // Tempory implementation
        // to avoid requirement of both new and old context
        // In <Toggle2Fa/>
        return onToggleChange(flag, callback);
      },

      onFlowTermination: () => {
        return callback(false);
      },
    });
  };

  render() {
    const { user } = this.props.user;
    const { toggleUser2FaEnforcement } = this.props;
    return (
      <Toggle2FA
        renderDescription={DescriptionForUser2Fa}
        renderTitle={TitleForUser2Fa}
        toggle2FaEnforcement={toggleUser2FaEnforcement}
        twoFaEnabled={user.second_factor_auth}
        onToggleComplete={this.onToggleComplete}
        getToggle2FaSuccessMsg={getToggle2FaSuccessMsg}
        confirmEnableMessage="Are you sure you want to enable 2-step verification for your user account?"
        confirmDisableMessage="Are you sure you want to disable 2-step verification for your user account?"
        onToggleChange={this.handleTwoFactorVerificationOnLoginToggle}
      />
    );
  }
}

function TitleForUser2Fa() {
  return (
    <span className="title">
      <strong>2 Step Verification </strong>
    </span>
  );
}

function DescriptionForUser2Fa() {
  return (
    <>
      <p>
        Add an extra layer of security to your account by using a one-time verification code in
        addition to your password each time you log in.
      </p>
      <ShowWhen additionalCondition={(user) => user.isAllowedTeamManagement}>
        <p>
          <strong>Note:</strong> You can setup 2FA for your team from{' '}
          <Link to="/team">manage team</Link> page
        </p>
      </ShowWhen>
    </>
  );
}

function getToggle2FaSuccessMsg(twoFaStatus) {
  return `2-step verification successfully turned ${twoFaStatus} for your account`;
}
