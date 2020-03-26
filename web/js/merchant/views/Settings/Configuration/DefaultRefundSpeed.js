import { Component } from 'react';
import { connect } from 'react-redux';
import { updateFeatures } from 'merchant/reducers/config';
import { showNotification } from 'merchant_common/reducers/notifications';
import ShowWhen from 'merchant/components/ShowWhen';
import SwitchField from 'common/ui/Forms/SwitchField';
import Input from 'common/new-ui/Input';
import Popover, { PopoverTitle, PopoverBody } from 'common/ui/Popover';
import { updateConfig } from 'merchant/reducers/config';
import { showWhenUtil } from 'merchant/components/ShowWhen';
import Amount from 'common/ui/Amount';

@connect(
  state => {
    return {
      user: state.session.user,
      features: state.config.features,
      default_refund_speed: state.config.config.default_refund_speed,
    };
  },
  { updateFeatures, showNotification, updateConfig }
)
export default class DefaultRefundSpeed extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };
  constructor(props) {
    super(props);
    this.state = {
      default_refund_speed: this.props.default_refund_speed,
    };
  }
  analytics = action => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settings',
      eventAction: `${action} - Instant Refunds`,
    });
  };
  hovered = false;
  checkDefaultRefundSpeed = speed => {
    if (speed === 'optimum') {
      let label;
      if (this.hovered) {
        label = `Setting Enable IR | Checked Pricing | Yes Enable`;
      } else {
        label = `Setting Enable IR | Didn't checked Pricing | Yes Enable`;
      }
      window.rzpAnalytics({
        eventCategory: 'Dashboard - Instant Refund',
        eventAction: 'Yes Enable',
        eventLabel: label,
      });
    }
    this.context
      .confirm({
        header: `Are you sure you want to enable ${
          speed === 'normal' ? 'normal' : 'instant'
        } refund?`,
        affirmativeLabel: 'Yes, Enable',
        message: () => (
          <React.Fragment>
            <div
              style={{ margin: '10px 0' }}
              class="change-default-refund-speed"
            >
              {speed == 'optimum' ? (
                <div>
                  Your payment will be refunded instantly at a minimal fee.
                  &nbsp;
                  {!showWhenUtil({
                    featureEnabled: 'card_transfer_refund',
                  }) ? (
                    <span>
                      <i class="i i-info-circle" />
                      <Popover
                        theme="dark"
                        align="bottom"
                        onMouseOver={() => (this.hovered = true)}
                        parentQuerySelector={`.Modal--confirm`}
                      >
                        <PopoverBody>
                          <div class="instant-breakup">
                            <div class="flex">
                              <div class="w50 text-left">Refund Amount</div>
                              <div class="w50 text-right">Fee Amount</div>
                            </div>
                            <hr
                              style={{
                                margin: 0,
                                marginBottom: '5px',
                                marginTop: '5px',
                              }}
                            />
                            <div class="flex">
                              <div class="w50 text-left">1-1000 INR</div>
                              <div class="w50 text-right">
                                <Amount value={499} currency={'INR'} />
                              </div>
                            </div>
                            <div class="flex">
                              <div class="w50 text-left">1001-25000 INR</div>
                              <div class="w50 text-right">
                                <Amount value={999} currency={'INR'} />
                              </div>
                            </div>
                            <div class="flex">
                              <div style={{ width: '60%' }} class="text-left">
                                25001 and above (INR)
                              </div>
                              <div style={{ width: '40%' }} class="text-right">
                                <Amount value={1999} currency={'INR'} />
                              </div>
                            </div>
                          </div>
                        </PopoverBody>
                      </Popover>
                    </span>
                  ) : null}
                </div>
              ) : (
                <div>Your payment will be refunded in 5-7 days*.</div>
              )}
            </div>
          </React.Fragment>
        ),
        affirmativePendingLabel: 'Updating...',
        abortLabel: "No, don't!",
        action: () => {
          this.updateDefaultRefundSpeed(speed);
        },
      })
      .catch(() => {});
  };

  updateDefaultRefundSpeed = speed => {
    this.props
      .updateConfig({
        default_refund_speed: speed,
      })
      .then(r => {
        if (r.data) {
          this.props.showNotification({
            type: 'success',
            message: 'Default Refund Speed Updated Successfully',
          });
          this.setState({
            default_refund_speed: speed,
          });
          this.props.closeModal();
        }
      })
      .catch(({ errors }) => {
        if (errors) {
          this.props.showNotification({
            type: 'error',
            message: errors,
          });
        }
      });
  };

  render() {
    return (
      <div class="panel panel-default refund-panel">
        <div class="panel-heading">
          <span class="title">
            Default Refund Speed{' '}
            <a
              class="highlight know-more"
              target="_blank"
              href="https://razorpay.com/docs/payment-gateway/instant-refunds/api"
            >
              API Reference Guide
              <i class="i i-external-link" style={{ marginLeft: '5px' }} />
            </a>
          </span>
        </div>

        <div class="panel-body">
          <div class="row">
            <div class="col-sm-6 p5">
              <div
                class={`refund-panel-col ${
                  this.state.default_refund_speed == 'normal' ? 'active' : ''
                }`}
              >
                <h4>
                  Normal Refund
                  <input
                    type="radio"
                    class="radio-pointer"
                    name="default_instant"
                    checked={this.state.default_refund_speed == 'normal'}
                    class="refund-speed-change-permission"
                    onChange={e => {
                      const speed = e.target.checked ? 'normal' : 'optimum';
                      this.checkDefaultRefundSpeed(speed);
                    }}
                  />
                </h4>
                <p>Your payment will be refunded in 5-7 days*.</p>
                <br />
                <span class="refunds-speed-tag">
                  Normal Speed &nbsp;
                  <span>
                    <i class="i i-help" />
                    <Popover align="right" theme="dark">
                      <PopoverBody>
                        <div style={{ textAlign: 'left' }}>
                          Your refund speed in API is normal
                        </div>
                      </PopoverBody>
                    </Popover>
                  </span>
                </span>
              </div>
            </div>
            <div class="col-sm-6 p5">
              <div
                class={`refund-panel-col ${
                  this.state.default_refund_speed == 'optimum' ? 'active' : ''
                }`}
              >
                <h4>
                  <i class="i i-instant-refund" />Instant Refund
                  <a
                    class="highlight know-more"
                    target="_blank"
                    href="https://razorpay.com/instant-refunds/"
                  >
                    Know more
                    <i
                      class="i i-external-link"
                      style={{ marginLeft: '5px' }}
                    />
                  </a>
                  {!showWhenUtil({
                    featureEnabled: 'disable_instant_refunds',
                  }) ? (
                    <input
                      type="radio"
                      class="radio-pointer"
                      checked={this.state.default_refund_speed == 'optimum'}
                      name="default_instant"
                      onChange={e => {
                        const speed = e.target.checked ? 'optimum' : 'normal';
                        this.checkDefaultRefundSpeed(speed);
                      }}
                      class="refund-speed-change-permission"
                    />
                  ) : null}
                </h4>
                {!showWhenUtil({
                  featureEnabled: 'disable_instant_refunds',
                }) ? (
                  <p>
                    Your payment will be refunded instantly at minimal fee*.
                  </p>
                ) : (
                  <p>
                    Instant refunds feature is disabled as per your request.
                  </p>
                )}

                <br />
                {!showWhenUtil({
                  featureEnabled: 'disable_instant_refunds',
                }) ? (
                  <span class="refunds-speed-tag">
                    Optimum Speed &nbsp;
                    <span>
                      <i class="i i-help" />
                      <Popover align="right" theme="dark">
                        <PopoverBody>
                          <div style={{ textAlign: 'left' }}>
                            Your refund speed in API is optimum
                          </div>
                        </PopoverBody>
                      </Popover>
                    </span>
                  </span>
                ) : (
                  <p>
                    {' '}
                    To enable it, please{' '}
                    <a
                      class="highlight know-more"
                      style={{ marginLeft: 0 }}
                      target="_blank"
                      href="https://razorpay.com/support/#request"
                    >
                      contact support
                    </a>
                  </p>
                )}
              </div>
            </div>{' '}
          </div>
        </div>
      </div>
    );
  }
}
