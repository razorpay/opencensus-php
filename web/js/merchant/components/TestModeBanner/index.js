import { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';
import { setItem } from 'common/utils/localStorage';
import Banner from 'common/ui/Banner';
import { trackLinkClick } from './ga';
import RTracking from 'react-tracking';

@RTracking(() => window.rzpQ.component('TestModeBanner'))
class TestModeBanner extends Component {
  switchToLiveMode = () => {
    const { user } = this.props;

    trackLinkClick('Swith - Mode');

    setItem(`rzp_mode--${user.current}`, 'live');
    window.location.reload();
  };

  render() {
    const { user, mode, tracking } = this.props;

    if (mode === 'live') {
      return null;
    }

    if (user.isOrgAxis) return null;

    return (
      /* For not as we have a seperarte Test Mode banner for m-web which is prominent so hiding this from m-web */
      <div className="TestModeBanner hidden-xs">
        <Banner>
          You are in <b>Test Mode</b>, so only test data is shown.{' '}
          {user.isActivated ? (
            <span>
              Switch to <a onClick={this.switchToLiveMode}>Live mode</a> to see real transaction
              data.
            </span>
          ) : null}
          {!user.isActivated && (
            <ShowWhen additionalCondition={(_user) => _user.isAllowedEdit('activation')}>
              <span>
                {' '}
                <Link
                  to="/activation"
                  onClick={() => {
                    trackLinkClick('Go To - Activation Form');
                    tracking.trackEvent(window.rzpQ.onbr().initiated('kyc.form_fill'));
                  }}
                >
                  Activate your account
                </Link>{' '}
                to start making live transactions.
              </span>
            </ShowWhen>
          )}
        </Banner>
      </div>
    );
  }
}

const mapStateToProps = (state) => {
  return state.session;
};

export default connect(mapStateToProps, null)(TestModeBanner);
