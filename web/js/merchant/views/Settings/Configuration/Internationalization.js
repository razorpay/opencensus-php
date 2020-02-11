import { Component } from 'react';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { updateSession } from 'merchant/reducers/session';
import { Link } from 'react-router-dom';
import { showNotification } from 'merchant_common/reducers/notifications';
import ShowWhen from 'merchant/components/ShowWhen';
import SwitchField from 'common/ui/Forms/SwitchField';
import { merchantFetch } from 'merchant/utils/ajax';
import Alert from 'common/new-ui/Alert';
import User from 'merchant/models/User';

const CUSTOM_MSG = {
  not_supported:
    'International card payments is not supported for your business model',
  incomplete_forms: (
    <span>
      You will have to submit your <Link to="/activation">Activation form</Link>{' '}
      & <Link to="/activation">KYC form</Link> in-order to accept international
      payments
    </span>
  ),
  generic_msg:
    'Settlement cycle and transaction fee is higher for International payments.',
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
@RTracking(() => window.rzpQ.component('FlashCheckout'))
export default class FlashCheckout extends Component {
  state = { internationalEnabled: this.props.user.international };

  analytics = action => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settings',
      eventAction: `${action} - International card payments`,
    });
  };

  @RTracking(() =>
    window.rzpQ.onbr().initiated('dash.settings_action', {
      action: 'Toggle_International_Payments',
    })
  )
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

          // Update user in store
          const user = new User({
            ...this.props.user,
            international: resp.data.international,
          });

          this.props.updateSession({ user });
        } else {
          throw 'We are unable to process this request. Please reach out to support@razorpay.com'; // This code is ideally unreachable as per business logic. However, since Api silently fails here, hence handling explicitly.
        }
      })
      .catch(err => {
        cb(false);

        this.props.showNotification({
          type: 'error',
          message: err.errors || err,
        });
      });
  };

  openTicketForm = () => {
    window && window.rzpTicketSystem.openModal('#ticket');
  };

  render() {
    let { internationalEnabled } = this.state;
    const { user } = this.props;

    let displayMsg = '',
      showToggler = false,
      showBanner = false; // Default diplomatic message - for existing merchants

    if (user.international) {
      displayMsg = CUSTOM_MSG['generic_msg'];

      if (user.international_activation_flow) {
        showToggler = true;
      }
    } else {
      // If international profiling(whitelist-blacklist-graylist) is set
      if (user.international_activation_flow) {
        /*
        * Blacklist(international) - show not supported
        * */
        if (user.internationalActivationFlow.isBlacklistFlow) {
          displayMsg = CUSTOM_MSG['not_supported'];
        } else if (user.internationalActivationFlow.isGraylistFlow) {
          displayMsg = (
            <>
              Please contact{' '}
              <span
                className="text-primary"
                role="button"
                onClick={this.openTicketForm}
              >
                Support.
              </span>
            </>
          );
        } else if (user.internationalActivationFlow.isWhitelistFlow) {
          showToggler = true;
          displayMsg = CUSTOM_MSG['generic_msg'];
        }
      } else {
        /*
         * For both new and old merchants - whose profiling is not done, also haven't submitted their L1 or L2 form.
         * Note: Here we're not checking international_activation_flow, cuz we just want to know if L1 form is filled or not.
         * */
        if (!user.activation_flow || !user.submitted) {
          // not needed, enable later
          // showBanner = true; // For these merchants, international will automatically be enabled for such merchants if eligible

          displayMsg = CUSTOM_MSG['incomplete_forms'];
        } else {
          displayMsg = CUSTOM_MSG['generic_msg'];
        }
      }
    }

    return (
      <div class="panel panel-default">
        <div class="panel-heading">
          <span class="title">International card payments</span>

          {user.has_key_access && (
            <>
              {showToggler && (
                <span class="toggler-btn">
                  <SwitchField
                    defaultChecked={!!internationalEnabled}
                    onChange={(isChecked, cb) =>
                      this.toggleInternationalization(isChecked, cb)
                    }
                    type="prime"
                  />
                  {user.international ? (
                    <b class="text-primary">Enabled</b>
                  ) : (
                    <b class="text-faded">Disabled</b>
                  )}
                </span>
              )}
            </>
          )}
        </div>

        <div class="panel-body">
          <form class="form-horizontal">
            {user.has_key_access ? (
              <>
                {showBanner && (
                  <Alert.Info>
                    From <b>1st April</b> your account will be activated to
                    accept international card payments based on your
                    eligibility, with support for 92 currencies
                  </Alert.Info>
                )}

                <div class="description">{displayMsg}</div>

                <div class="form-group">
                  <ShowWhen
                    additionalCondition={user =>
                      user.isOrgAllowedFunctionality('external_links')
                    }
                  >
                    <div class="col-sm-10">
                      <a
                        class="highlight"
                        target="_blank"
                        href="https://razorpay.com/payment-gateway/#go-international"
                      >
                        Know more
                        <i
                          class="i i-external-link"
                          style={{ marginLeft: '5px' }}
                        />
                      </a>
                    </div>
                  </ShowWhen>
                </div>
              </>
            ) : (
              <div class="description">
                Accepting international payments via cards is currently
                available for Razorpay products – Payment Gateway, Payment
                Links, Payment Pages, Subscriptions and Invoices.
                <br />
                <br />
                Route Transfers and payments collected via Smart Collect do not
                have international support.
              </div>
            )}
          </form>
        </div>
      </div>
    );
  }
}
