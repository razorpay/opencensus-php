import { Component } from 'react';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';
import ShowWhen from 'merchantLA/components/ShowWhen';
import LocalStorageService from 'rzp/utils/localStorage';
import Banner from 'rzp/ui/Banner';
import { trackLinkClick } from './ga';

@connect(state => state.session)
export default class TestModeBanner extends Component {
  switchToLiveMode = () => {
    const { user } = this.props;

    trackLinkClick('Swith - Mode');

    LocalStorageService.setItem(`rzp_mode--${user.current}`, 'live');
    window.location.reload();
  };

  render() {
    let { user, mode } = this.props;

    if (mode === 'live') {
      return null;
    }

    return (
      <div className="TestModeBanner">
        <Banner>
          You are in <b>Test Mode</b>, so only test data is shown.{' '}
          {user.isActivated ? (
            <span>
              Switch to <a onClick={this.switchToLiveMode}>Live mode</a> to see
              real transaction data.
            </span>
          ) : null}
          {!user.isActivated && (
            <ShowWhen myRole="owner manager admin">
              <span>
                {' '}
                <Link
                  to="/activation"
                  onClick={() => trackLinkClick('Go To - Activation Form')}
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
