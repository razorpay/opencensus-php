import { Component } from 'react';
import { connect } from 'react-redux';
import { updateFeatures } from 'merchant/modules/config';
import { showNotification } from 'rzp/modules/notifications';
import ShowWhen from 'merchant/components/ShowWhen';
import SwitchField from 'rzp/ui/Forms/SwitchField';

const CUSTOM_MSG = {
  not_supported:
    'International card payments is not supported to your business model.',
  kyc_pending:
    'Your KYC has to be approved in-order to accept International card payments.',
  activation_pending:
    'You will have to fill your Activation form & KYC form to be eligible to receive International card payments.',
  international_allowed:
    'Settlement cycle and transaction fee is higher for International payments. \n International card payments is currently available only for payment gateways and not for payment pages, payment links & invoices.',
};

@connect(
  state => {
    return {
      user: state.session.user,
      features: state.config.features,
    };
  },
  { updateFeatures, showNotification }
)
export default class FlashCheckout extends Component {
  constructor(props) {
    super(props);
    this.state = {};

    if (props.features.length) {
      const fcEnabled = this.getFlashCheckoutFlag(props.features);
      this.state.fcEnabled = fcEnabled;
    }
  }

  componentWillReceiveProps(nextProps) {
    if (!this.props.features.length && nextProps.features.length) {
      const fcEnabled = this.getFlashCheckoutFlag(nextProps.features);

      this.setState({ fcEnabled });
    }
  }

  getFlashCheckoutFlag(features) {
    let noFlashCheckout =
      features.find(feature => feature.feature === 'noflashcheckout') || {};

    this.setState({ fcEnabled: !noFlashCheckout.value });
  }

  analytics = action => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settings',
      eventAction: `${action} - Internationalization`,
    });
  };

  toggleInternationalization = () => {
    let fcEnabled = this.state.fcEnabled;
    let shouldSync = 1;
    var data = {
      features: {
        noflashcheckout: fcEnabled ? 1 : 0,
      },
      should_sync: shouldSync,
    };

    return this.props
      .updateFeatures(data, this.props.user.current)
      .then(res => {
        if (fcEnabled) {
          this.analytics('Disable');
        } else {
          this.analytics('Enable');
        }
        this.props.showNotification({
          type: 'success',
          message: 'Your preference was saved',
        });
        this.setState({
          fcEnabled: !fcEnabled,
        });
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  render() {
    let { fcEnabled } = this.state;

    let display_msg = '';
    const isInternationalAllowed =
      true || (user.international || user.isWhitelistFlow);

    if (isInternationalAllowed) {
      display_msg = CUSTOM_MSG['international_activated'];
    } else if (user.isBlacklistFlow) {
      display_msg = CUSTOM_MSG['not_supported'];
    } else if (user.isGraylistFlow) {
      display_msg = CUSTOM_MSG['kyc_pending'];
    }

    return (
      <div className="panel panel-default">
        <div className="panel-heading">
          <span className="title">International card payments</span>

          <span className="toggler-btn">
            <SwitchField
              defaultChecked={!!fcEnabled}
              onChange={(isChecked, cb) => this.toggleFc(isChecked, cb)}
              type="prime"
            />
            {fcEnabled ? (
              <b className="text-primary">Enabled</b>
            ) : (
              <b className="text-faded">Disabled</b>
            )}
          </span>
        </div>

        <div className="panel-body">
          <form className="form-horizontal">
            {isInternationalAllowed && (
              <banner className="info">
                From <b>3rd of March</b> your account will be activated for
                international card payments, supporting 92 international
                currencies.
              </banner>
            )}

            <div className="description">{display_msg}</div>

            <div className="form-group">
              <ShowWhen
                additionalCondition={user =>
                  user.isOrgAllowedFunctionality('external_links')
                }
              >
                <div className="col-sm-10">
                  <a
                    className="highlight"
                    target="_blank"
                    href="https://razorpay.com/"
                  >
                    Know more
                    <i
                      className="i i-external-link"
                      style={{ marginLeft: '5px' }}
                    />
                  </a>
                </div>
              </ShowWhen>
            </div>
          </form>
        </div>
      </div>
    );
  }
}
