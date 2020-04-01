import { Component } from 'react';
import { connect } from 'react-redux';
import { getOnboardingStatus, onboardTerminal } from 'merchant/reducers/config';
import ShowWhen from 'merchant/components/ShowWhen';
import SwitchField from 'common/ui/Forms/SwitchField';
import { DisputeStatusLabel as StatusLabel } from 'merchant/components/StatusLabel';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { showNotification } from 'merchant_common/reducers/notifications';

@connect(
  state => {
    return {
      user: state.session.user,
      features: state.config.features,
      terminals: state.config.paypal_terminals,
    };
  },
  { getOnboardingStatus, onboardTerminal, showNotification }
)
export default class PaypalOnboarding extends Component {
  constructor(props) {
    super(props);
  }

  state = {
    loading: false,
    terminals: [],
  };

  getOnboardingStatus = () => {
    return this.props
      .getOnboardingStatus('wallet_paypal')
      .then(res => {
        this.setState({ terminals: res.data.items });
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message:
            'The server encountered an error. The incident has been reported to admins.',
        });
      });
  };

  verifyAccount = () => {
    this.setState({ loading: true });
    onboardTerminal('wallet_paypal')
      .then(res => {
        const w = 520;
        const h = 570;
        var left = screen.width / 2 - w / 2;
        var top = screen.height / 2 - h / 2;

        const win = window.open(
          res.data.links,
          null,
          `location=yes,height=${h},width=${w},scrollbars=yes,status=yes,left=${left},top=${top}`
        );
        window.addEventListener('message', e => {
          if (e.data === 'paypal_onboard_redirect') {
            window.focus();
            win.close();
          }
        });
        win.window.focus();
        var interval = setInterval(() => {
          if (win && win.closed) {
            this.getOnboardingStatus();
            this.setState({ loading: false });
            clearInterval(interval);
          }
        }, 400);
      })
      .catch(err => {
        this.setState({ loading: false });
        this.props.showNotification({
          type: 'error',
          message:
            'The server encountered an error. The incident has been reported to admins.',
        });
      });
  };

  componentDidMount() {
    this.getOnboardingStatus();
  }

  render() {
    let status = this.props.terminals.length && this.props.terminals[0].status;
    const showStatus =
      ['created', 'activated', 'rejected', 'pending'].indexOf(status) !== -1;
    return (
      <React.Fragment>
        <div class="panel panel-default paypal-auto-onboarding">
          <div class="panel-heading">
            <span class="title">Paypal </span>{' '}
            <a
              class={`highlight ${showStatus ? 'know-more' : ''}`}
              target="_blank"
              style={{ marginLeft: '10px' }}
              href="https://razorpay.com/docs/payment-methods/paypal"
            >
              Know more
              <i class="i i-external-link" style={{ marginLeft: '5px' }} />
            </a>
            {showStatus ? (
              <span
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
                <span class="status-text">
                  {status == 'created' ? 'pending' : status}
                </span>{' '}
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
          </div>

          <div class="panel-body">
            <div
              class="description"
              style={{ marginTop: 0, marginBottom: '15px' }}
            >
              Accept international payments using PayPal on Razorpay Checkout.
            </div>
            {status === 'requested' || this.props.terminals.length === 0 ? (
              <button
                disabled={this.state.loading}
                onClick={this.verifyAccount}
                class="btn btn-primary paypal-onboard-button"
              >
                {' '}
                <i class="i i-paypal paypal-onboard-icon" />{' '}
                {this.state.loading ? 'Processing..' : 'Link Account'}
              </button>
            ) : null}
          </div>
        </div>
      </React.Fragment>
    );
  }
}

const STATUSES = ['requested', 'created', 'activated', 'rejected', 'pending'];
