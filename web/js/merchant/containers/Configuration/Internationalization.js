import { Component } from 'react';
import { connect } from 'react-redux';
import { updateSession } from 'merchant/modules/session';
import { showNotification } from 'rzp/modules/notifications';
import ShowWhen from 'merchant/components/ShowWhen';
import SwitchField from 'rzp/ui/Forms/SwitchField';
import { merchantFetch } from 'merchant/utils/ajax';

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
  { updateSession, showNotification }
)
export default class FlashCheckout extends Component {
  state = { internationalEnabled: this.props.user.international };

  analytics = action => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settings',
      eventAction: `${action} - Internationalization`,
    });
  };

  toggleInternationalization = (enableInternational, cb) => {
    this.analytics(enableInternational ? 'Enable' : 'Disable');

    return merchantFetch({
      url: 'merchant/international',
      method: 'PATCH',
      data: {
        international: enableInternational ? 1 : 0,
      },
    })
      .then(resp => {
        // Check if the response sets international as intended in this request
        if (resp.data.international === !!enableInternational) {
          cb(true);
          this.setState({
            internationalEnabled: !!enableInternational,
          });

          this.props.updateSession(resp.data);
        } else {
          throw 'Business category/subcategory must be set'; // This code is ideally unreachable as per business logic. However, since Api silently fails here, hence handling explicitly.
        }
      })
      .catch(err => {
        cb(false);

        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
      });
  };

  render() {
    let { internationalEnabled } = this.state;
    const { user } = this.props;

    let display_msg = '';
    const isInternationalAllowed =
      user.international || user.internationalActivationFlow.isWhitelistFlow;

    if (isInternationalAllowed) {
      display_msg = CUSTOM_MSG['international_activated'];
    } else if (user.internationalActivationFlow.isBlacklistFlow) {
      display_msg = CUSTOM_MSG['not_supported'];
    } else if (user.internationalActivationFlow.isGraylistFlow) {
      display_msg = CUSTOM_MSG['kyc_pending'];
    }

    return (
      <div className="panel panel-default">
        <div className="panel-heading">
          <span className="title">International card payments</span>

          <span className="toggler-btn">
            <SwitchField
              defaultChecked={!!internationalEnabled}
              onChange={(isChecked, cb) =>
                this.toggleInternationalization(isChecked, cb)
              }
              type="prime"
            />
            {internationalEnabled ? (
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
