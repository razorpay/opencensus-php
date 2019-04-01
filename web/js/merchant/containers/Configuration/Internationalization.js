import { Component } from 'react';
import { connect } from 'react-redux';
import { updateSession } from 'merchant/modules/session';
import { Link } from 'react-router-dom';
import { showNotification } from 'rzp/modules/notifications';
import ShowWhen from 'merchant/components/ShowWhen';
import SwitchField from 'rzp/ui/Forms/SwitchField';
import { merchantFetch } from 'merchant/utils/ajax';
import Alert from 'component/Alert';
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
  kyc_pending: (
    <span>
      Your <Link to="/activation">KYC form</Link> has to be approved in-order to
      accept International card payments.
    </span>
  ),
  generic_msg:
    'Settlement cycle and transaction fee is higher for International payments. \n International card payments is currently available only for payment gateway and not for payment pages, payment links & invoices.',
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
      eventAction: `${action} - International card payments`,
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
        * For GrayList(international) if L1 submitted but not L2.
        * */
        if (
          !user.submitted &&
          user.internationalActivationFlow.isGraylistFlow
        ) {
          displayMsg = CUSTOM_MSG['kyc_pending'];
        } else if (user.internationalActivationFlow.isBlacklistFlow) {
          displayMsg = CUSTOM_MSG['not_supported'];
        } else if (
          user.internationalActivationFlow.isWhitelistFlow ||
          (user.activation_status === 'activated' &&
            user.internationalActivationFlow.isGraylistFlow)
        ) {
          // To show only for newly activated merchants (whitelist and activated graylist only)

          showToggler = true;
          displayMsg = CUSTOM_MSG['generic_msg'];
        } else if (user.internationalActivationFlow.isGraylistFlow) {
          // If not activated
          if (user.activation_status !== 'activated') {
            displayMsg = CUSTOM_MSG['generic_msg'];
          }

          // If L2 form submitted but KYC approval is pending.
          if (user.submitted) {
            displayMsg = CUSTOM_MSG['kyc_pending'];
          }
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
      <div className="panel panel-default">
        <div className="panel-heading">
          <span className="title">International card payments</span>

          {
            user.has_key_access && (
              <>
                {showToggler && (
                  <span className="toggler-btn">
                    <SwitchField
                      defaultChecked={!!internationalEnabled}
                      onChange={(isChecked, cb) =>
                        this.toggleInternationalization(isChecked, cb)
                      }
                      type="prime"
                    />
                    {user.international ? (
                      <b className="text-primary">Enabled</b>
                    ) : (
                      <b className="text-faded">Disabled</b>
                    )}
                  </span>
                )}
              </>
            ) 
          }

        </div>

        <div className="panel-body">
          <form className="form-horizontal">
            {
              user.has_key_access ? (
                <>
                  {showBanner && (
                    <Alert.Info>
                      From <b>1st April</b> your account will be activated to accept
                      international card payments based on your eligibility, with
                      support for 92 currencies
                    </Alert.Info>
                  )}

                  <div className="description">{displayMsg}</div>

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
                          href="https://razorpay.com/payment-gateway/#go-international"
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
                </> 
                ) : (
                  <div class="description">
                    International card payments are currently available only for payment gateway [which requires website integeration] and not for payment pages, payment links & invoices. We are working on bringing the international support to other products soon
                  </div>
                )
            }
          </form>
        </div>
      </div>
    );
  }
}
