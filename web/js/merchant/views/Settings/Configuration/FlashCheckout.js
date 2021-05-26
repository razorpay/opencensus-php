import { Component } from 'react';
import { connect } from 'react-redux';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { updateFeatures } from 'merchant/reducers/config';
import { showNotification } from 'merchant_common/reducers/notifications';
import ShowWhen from 'merchant/components/ShowWhen';
import SwitchField from 'common/ui/Forms/SwitchField';
import { getCustomURL } from 'merchant/components/DocsLink';
@connect(
  (state) => {
    return {
      user: state.session.user,
      features: state.config.features,
    };
  },
  { updateFeatures, showNotification },
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
    let noFlashCheckout = features.find((feature) => feature.feature === 'noflashcheckout') || {};

    const fcEnabled = !noFlashCheckout.value;
    return fcEnabled;
  }

  analytics = (action) => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settings',
      eventAction: `${action} - Flash Checkout`,
    });
  };

  toggleFc = (enableFC, cb) => {
    let shouldSync = 0;
    var data = {
      features: {
        noflashcheckout: this.state.fcEnabled,
      },
      should_sync: shouldSync,
    };

    analyticsTrack({
      objectName: 'flash checkout',
      actionName: 'toggled',
      screen: 'settings',
      properties: {
        location: 'configuration',
        flashCheckout: enableFC ? 'Enabled' : 'Disabled',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    return this.props
      .updateFeatures(data, this.props.user.current)
      .then((res) => {
        cb(true);

        if (enableFC) {
          this.analytics('Enable');
        } else {
          this.analytics('Disable');
        }
        this.props.showNotification({
          type: 'success',
          message: 'Your preference was saved',
        });
        analyticsTrack({
          objectName: 'flash checkout toggle',
          actionName: 'result',
          screen: 'settings',
          properties: {
            location: 'configuration',
            status: 'Success',
            flashCheckout: enableFC ? 'Enabled' : 'Disabled',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
        this.setState({
          fcEnabled: !this.state.fcEnabled,
        });
      })
      .catch((err) => {
        cb(false);

        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });
        analyticsTrack({
          objectName: 'flash checkout toggle',
          actionName: 'result',
          screen: 'settings',
          properties: {
            location: 'configuration',
            status: 'Failure',
            flashCheckout: enableFC ? 'Enabled' : 'Disabled',
            failureReason: err.errors[0],
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      });
  };

  render() {
    let { fcEnabled } = this.state;
    const {
      org: { custom_code },
    } = this.props;

    return (
      <div class="panel panel-default">
        <div class="panel-heading">
          <span class="title">Flash Checkout</span>

          <span class="toggler-btn">
            <SwitchField
              defaultChecked={!!fcEnabled}
              onChange={(isChecked, cb) => this.toggleFc(isChecked, cb)}
              type="prime"
            />
            {fcEnabled ? <b class="text-primary">Enabled</b> : <b class="text-faded">Disabled</b>}
          </span>
        </div>

        <div class="panel-body">
          <form class="form-horizontal">
            <div class="description">
              Securely save the card details of your customers, with Razorpay's Flash Checkout.
            </div>

            <div class="form-group">
              <ShowWhen
                additionalCondition={(user) => user.isOrgAllowedFunctionality('external_links')}
              >
                <div class="col-sm-10">
                  <a
                    class="highlight"
                    target="_blank"
                    href={getCustomURL('https://razorpay.com/flashcheckout/')}
                    onClick={() =>
                      analyticsTrack({
                        objectName: 'know more',
                        actionName: 'clicked',
                        screen: 'settings',
                        properties: {
                          location: 'configuration',
                          flowName: 'Flash Checkout',
                          ...getCommonAnalyticsProperties(window.rzp_user),
                        },
                      })
                    }
                  >
                    Know more
                    <i class="i i-external-link" style={{ marginLeft: '5px' }} />
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
