import { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import ShowWhen from 'merchantLA/components/ShowWhen';
import { withI18Service } from 'common/i18';
import Banner from 'common/ui/Banner';
import { trackLinkClick } from './ga';
import { switchMode } from '@libs/shared-utils';

class TestModeBanner extends Component {
  switchToLiveMode = () => {
    const { user } = this.props;

    trackLinkClick('Switch - Mode');

    switchMode(user.current, 'live');

    window.location.reload();
  };

  render() {
    const {
      user,
      mode,
      i18: { isConfigTagEnabled },
    } = this.props;

    if (mode === 'live') {
      return null;
    }

    return (
      <div className="TestModeBanner">
        <Banner>
          You are in <b>Test Mode</b>, so only test data is shown.{' '}
          {user.isActivated ? (
            <span>
              Switch to <a onClick={this.switchToLiveMode}>Live mode</a> to see real transaction
              data.
            </span>
          ) : null}
          {!user.isActivated && (
            <ShowWhen myRole="owner manager admin">
              {isConfigTagEnabled('onboarding.onboarding') ? (
                <span>Activate your account to start making live transactions.</span>
              ) : (
                <span>
                  <Link to="/activation" onClick={() => trackLinkClick('Go To - Activation Form')}>
                    Activate your account
                  </Link>
                  to start making live transactions.
                </span>
              )}
            </ShowWhen>
          )}
        </Banner>
      </div>
    );
  }
}

export default connect((state) => state.session)(withI18Service(TestModeBanner));
