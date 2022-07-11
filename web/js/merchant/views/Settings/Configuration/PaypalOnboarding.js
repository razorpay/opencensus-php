import React, { Component } from 'react';
import { connect } from 'react-redux';
import { getOnboardingStatus, onboardTerminal } from 'merchant/reducers/config';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { getClassName, getStatusMessage } from './InternationalPayments';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';

class PaypalOnboardingButton extends Component {
  is_redirected = false;
  state = {
    loading: false,
  };

  getOnboardingStatus = () => {
    return this.props
      .getOnboardingStatus('wallet_paypal')
      .then(() => {
        return Promise.resolve();
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  verifyAccount = () => {
    this.setState({ loading: true });
    selfServeTrackInitiate({
      selfServeAction: 'International Payments Applied',
      page: 'Config',
      screen: 'Settings',
    });
    analyticsTrack({
      objectName: 'Paypal Change Account',
      actionName: 'clicked',
      screen: 'settings',
      properties: {
        location: '',
        actionName: 'Change Account',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    onboardTerminal('wallet_paypal')
      .then((res) => {
        const w = 520;
        const h = 570;
        const left = screen.width / 2 - w / 2;
        const top = screen.height / 2 - h / 2;

        const win = window.open(
          res.data.links,
          null,
          `location=yes,height=${h},width=${w},scrollbars=yes,status=yes,left=${left},top=${top}`,
        );
        window.addEventListener('message', (e) => {
          if (e.data === 'paypal_onboard_redirect') {
            this.is_redirected = true;
            window.focus();
            win.close();
          }
        });
        if (win && win.window) {
          win.window.focus();
        }

        const interval = setInterval(() => {
          if (win && win.closed) {
            if (this.is_redirected) {
              onboardTerminal('wallet_paypal').finally(() => {
                this.props
                  .getOnboardingStatus('wallet_paypal')
                  .then(() => this.props.getOnboardingStatus('wallet_paypal'))
                  .then(() => {
                    this.setState({
                      loading: false,
                    });
                  });
              });
            } else {
              this.setState({ loading: false });
            }
            this.is_redirected = false;
            clearInterval(interval);
          }
        }, 400);
      })
      .catch((err) => {
        this.setState({ loading: false });
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  componentDidMount() {
    this.getOnboardingStatus();
  }

  render() {
    const {
      disabled,
      disabledText,
      terminals,
      showLogo,
      user,
      /* eslint-disable no-shadow */
      openModal,
      closeModal,
      /* eslint-disable no-shadow */
      isInternationalPayment,
    } = this.props;

    const status = terminals.length && terminals[0].terminal.status;
    const showLinkButtonOnly = terminals.length === 0;
    return (
      <div className={showLinkButtonOnly ? 'link-account' : 'change-account'}>
        {isInternationalPayment && !showLinkButtonOnly && status !== 'activated' && (
          <div className="change-account-action">
            <a className="merchant-id">
              <i className="i i-user-circle" /> {terminals[0].terminal.merchant_id}
              <Popover align="right" theme="dark">
                <PopoverBody>
                  <div className="disabled-text">Paypal generated Merchant ID</div>
                </PopoverBody>
              </Popover>
            </a>

            <a
              onClick={() =>
                openModal({
                  size: 'small',
                  className: 'change-account-modal',
                  component: (
                    <ChangeAccountModal
                      changeAccount={this.verifyAccount}
                      onClose={closeModal}
                      user={user}
                    />
                  ),
                })
              }
            >
              Change Account
            </a>
          </div>
        )}

        {['pending', 'created', 'requested', 'permission_missing'].includes(status) && (
          <p className={`status status-${getClassName(status)}`}>
            <i className="i i-info-circle" /> {getStatusMessage(status)}
          </p>
        )}
        {showLinkButtonOnly ? (
          <>
            <button
              disabled={this.state.loading || disabled}
              onClick={this.verifyAccount}
              className="btn btn-primary paypal-onboard-button"
            >
              {' '}
              {showLogo && (
                <img
                  className="paypal-onboard-img"
                  src="https://cdn.razorpay.com/static/assets/paypal.svg"
                />
              )}
              {this.state.loading ? 'Processing..' : 'Link Account'}
            </button>
            {disabled && (
              <Popover align="right" theme="dark">
                <PopoverBody>
                  <div className="disabled-text">{disabledText}</div>
                </PopoverBody>
              </Popover>
            )}
          </>
        ) : null}
        {!isInternationalPayment && !showLinkButtonOnly && status !== 'activated' && (
          <div className="change-account-action">
            <a
              onClick={() =>
                openModal({
                  size: 'small',
                  className: 'change-account-modal',
                  component: (
                    <ChangeAccountModal
                      changeAccount={this.verifyAccount}
                      onClose={closeModal}
                      user={user}
                    />
                  ),
                })
              }
            >
              Change Account
            </a>
          </div>
        )}
      </div>
    );
  }
}

const ChangeAccountModal = (props) => {
  const { changeAccount, onClose } = props;
  return (
    <div>
      <div className="header">
        <p className="title">Change Paypal Account</p>
        <i className="i i-close" onClick={() => onClose()} />
      </div>
      <div className="body">
        {/* <div className="status">
          <strong>Account in use</strong>
          <p>{user.email}</p>
        </div> */}
        <div className="message">
          You can link only one <b>PayPal account</b> with Razorpay. Are you sure you want to remove
          the existing and link new?
        </div>
        <button onClick={() => changeAccount()} className="btn btn-primary paypal-onboard-button">
          <img
            className="paypal-onboard-img"
            src="https://cdn.razorpay.com/static/assets/paypal.svg"
          />{' '}
          Link New Account
        </button>
      </div>
    </div>
  );
};

PaypalOnboardingButton.defaultProps = {
  showLogo: true,
};

function mapStateToProps(state) {
  return {
    user: state.session.user,
    features: state.config.features,
    terminals: state.config.paypal_terminals,
  };
}

export default connect(mapStateToProps, {
  getOnboardingStatus,
  onboardTerminal,
  showNotification,
  openModal,
  closeModal,
})(PaypalOnboardingButton);
