import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import { toggleUser2FaEnforcement } from 'merchant/reducers/team';
import { updateSession } from 'merchant/reducers/session';

import User from 'merchant/models/User';

import Toggle2FA from '../../components/TwoFAVerification/Toggle2FA';

@connect(state => ({ user: state.session.user }), {
  toggleUser2FaEnforcement,
  updateSession,
})
export default class User2FASettings extends React.PureComponent {
  onToggleComplete = twoFaEnabled => {
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
        Add an extra layer of security to your account by using a one-time
        verification code in addition to your password each time you log in.
      </p>
      <p>
        <strong>Note:</strong> You can setup 2FA for your team from{' '}
        <Link to="/team">manage team</Link> page
      </p>
    </>
  );
}

function getToggle2FaSuccessMsg(twoFaStatus) {
  return `2-step verification successfully turned ${twoFaStatus} for your account`;
}
