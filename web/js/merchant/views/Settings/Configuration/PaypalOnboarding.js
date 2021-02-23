import { Component } from 'react';
import { connect } from 'react-redux';
import { getOnboardingStatus, onboardTerminal } from 'merchant/reducers/config';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { showNotification } from 'merchant_common/reducers/notifications';

@connect(
  (state) => {
    return {
      user: state.session.user,
      features: state.config.features,
      terminals: state.config.paypal_terminals,
    };
  },
  { getOnboardingStatus, onboardTerminal, showNotification },
)
export default class PaypalOnboardingButton extends Component {
  constructor(props) {
    super(props);
  }
  is_redirected = false;
  state = {
    loading: false,
    terminals: [],
  };

  getOnboardingStatus = () => {
    return this.props
      .getOnboardingStatus('wallet_paypal')
      .then((res) => {
        this.setState({ terminals: res.data.items });
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
    onboardTerminal('wallet_paypal')
      .then((res) => {
        const w = 520;
        const h = 570;
        var left = screen.width / 2 - w / 2;
        var top = screen.height / 2 - h / 2;

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

        var interval = setInterval(() => {
          if (win && win.closed) {
            if (this.is_redirected) {
              onboardTerminal('wallet_paypal').finally(() => {
                this.props
                  .getOnboardingStatus('wallet_paypal')
                  .then(() => this.props.getOnboardingStatus('wallet_paypal'))
                  .then((res) => {
                    this.setState({
                      terminals: res.data.items,
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
    let status = this.props.terminals.length && this.props.terminals[0].status;
    let showStatus = ['created', 'activated', 'rejected', 'pending'].indexOf(status) !== -1;
    return (
      <React.Fragment>
        {showStatus ? (
          <span
            style={{ marginLeft: '20px', float: 'right' }}
            class={`status-pill status-pill-${(() => {
              if (status === 'activated') {
                return 'success';
              }
              if (status === 'rejected') {
                return 'danger';
              }
              if (status === 'pending' || status === 'created') {
                return 'warning';
              }
            })()}`}
          >
            <span class="status-text">{status == 'created' ? 'pending' : status}</span>{' '}
            <span>
              <i class="i i-info-circle" />
              <Popover theme="dark" align="bottom">
                <PopoverBody>
                  <div>
                    {(() => {
                      if (status === 'activated') {
                        return 'PayPal has been activated as a payment method.';
                      }
                      if (status === 'rejected') {
                        return 'Your account is rejected by PayPal.';
                      }
                      if (status === 'pending') {
                        return 'Your account is pending for approval by PayPal.';
                      }
                      if (status === 'created') {
                        return 'Initiate Paypal approval for your account by verifiying your email.';
                      }
                    })()}
                  </div>
                </PopoverBody>
              </Popover>
            </span>{' '}
          </span>
        ) : null}
        {status === 'requested' || this.props.terminals.length === 0 ? (
          <button
            disabled={this.state.loading}
            onClick={this.verifyAccount}
            class="btn btn-primary paypal-onboard-button"
          >
            {' '}
            {this.props.showLogo && (
              <img
                class="paypal-onboard-img"
                src="https://cdn.razorpay.com/static/assets/paypal.svg"
              />
            )}
            {this.state.loading ? 'Processing..' : 'Link Account'}
          </button>
        ) : null}
      </React.Fragment>
    );
  }
}

PaypalOnboardingButton.defaultProps = {
  showLogo: true,
};
