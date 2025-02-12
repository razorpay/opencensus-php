import { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { compose } from 'redux';

import { withI18Service } from 'common/i18';
import Popover, { PopoverBody } from 'common/ui/Popover';
import TextHighlighter from 'common/ui/TextHighlighter';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { getCustomURL } from 'merchant/components/DocsLink';
import { showWhenUtil } from 'merchant/components/ShowWhen';
import {
  updateFeatures,
  updateConfig,
  fetchRefundPricing,
  createLateAuthConfig,
  fetchFeatureStatus,
} from 'merchant/reducers/config';
import { updateMerchant as updateMerchantReducer } from 'merchant/reducers/session';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import EnableInstantRefundsModal from 'merchant/views/Transactions/v1/Payments/components/EnableInstantRefundsModal';
import InstantRefundFee from 'merchant/views/Transactions/v1/Payments/components/InstantRefundFee';
import { openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import { REFUND_SETTINGS } from './deeplink-constants';

const raiseTicket = () => {
  if (window.rzpTicketSystem) {
    const rzpTicketSystem = window.rzpTicketSystem;
    CreateTicketEmitter.emit(
      'create-ticket',
      'ticket',
      () => {
        rzpTicketSystem?.setPrefill('#request', ['merchant', 'other']);
      },
      () => {
        setTimeout(() => {
          rzpTicketSystem?.modal?.next();
        }, 0);
      },
    );

    setTimeout(() => {
      const el = document.getElementsByName('request-description')[0];
      el.value = 'Hello Team,\nI’d like to enable Instant Refund feature';
      el.focus();
    }, 1000);
  }
};
class DefaultRefundSpeed extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };
  constructor(props) {
    super(props);
    this.state = {
      default_refund_speed: this.props.default_refund_speed,
      isInstantRefundOrg: false,
      isInstantRefundMid: false,
    };
  }
  analytics = (action) => {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Settings',
      eventAction: `${action} - Instant Refunds`,
    });
  };
  hovered = false;

  UNSAFE_componentWillMount() {
    // check paypal org feature
    if (this.props.org?.features.indexOf('enable_refunds') > -1) {
      this.setState({
        isInstantRefundOrg: true,
      });

      // check paypal MID feature
      this.props
        .fetchFeatureStatus(this.props.user.id, 'merchant_enable_refunds')
        .then((fetchFeatureStatusResp) => {
          if (fetchFeatureStatusResp?.data?.status) {
            this.setState({
              isInstantRefundMid: true,
            });
          }
        })
        .catch((err) => {
          if (err) {
            this.props.showNotification({
              type: 'error',
              message: err.errors[0],
            });
          }
        });
    }
  }

  componentDidMount() {
    this.props.fetchRefundPricing();
  }

  changeDefaultRefundSpeed = (speed) => {
    this.props.tracking.trackEvent(
      window.rzpQ
        .merchantActions()
        .initiated(`Click - ${speed === 'normal' ? 'Normal' : 'Instant'} Refund Radio Button`, {
          label: 'Setting Page',
          session_id: window.session_id,
          category: 'Merchant Dashboard - IR',
        }),
    );

    analyticsTrack({
      objectName: `${speed === 'normal' ? 'normal' : 'instant'} refund`,
      actionName: 'clicked',
      screen: 'settings',
      properties: {
        location: 'configuration',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    this.props.openModal({
      component: (
        <EnableInstantRefundsModal
          pricing={this.props.refund_pricing}
          updated={() => this.updateDefaultRefundSpeed(speed)}
          speed={speed}
          openedFrom="Announcement"
        />
      ),
      size: 'small',
    });
  };

  checkDefaultRefundSpeed = (speed) => {
    this.changeDefaultRefundSpeed(speed);
  };

  updateDefaultRefundSpeed = (speed) => {
    this.props
      .updateConfig({
        default_refund_speed: speed,
      })
      .then(() => {
        this.props.updateMerchant({ default_refund_speed: speed });
        this.setState(
          {
            default_refund_speed: speed,
          },
          () => {
            this.updateLateAuthConfig(speed);
          },
        );
        this.props.closeModal();
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

  updateLateAuthConfig = (speed) => {
    const {
      data: { items },
      error,
    } = this.props.lateAuthConfig;

    if (error || !items || items.length === 0) return;
    const capture_config = items[0];
    capture_config.config.capture_options.refund_speed = speed;
    delete capture_config.name;
    delete capture_config.merchant_id;
    delete capture_config.entity;
    delete capture_config.is_default;
    delete capture_config.created_at;
    delete capture_config.updated_at;

    capture_config.type = 'late_auth';

    if (capture_config.config.capture_options.automatic_expiry_period === null)
      delete capture_config.config.capture_options.automatic_expiry_period;

    if (capture_config.config.capture_options.manual_expiry_period === null)
      delete capture_config.config.capture_options.manual_expiry_period;

    this.props.createLateAuthConfig(capture_config, 'patch');
  };

  render() {
    const { isInstantRefundOrg, isInstantRefundMid } = this.state;
    const { isConfigTagEnabled } = this.props.i18;

    const feeBearerValue = this.props.user?.merchant?.fee_bearer;

    return (
      <div id="default-refund-container" className="panel panel-default refund-panel">
        <div className="panel-heading pl10">
          <span className="title">
            <TextHighlighter hashedWith={REFUND_SETTINGS}>Default Refund Speed</TextHighlighter>{' '}
            <a
              className="highlight know-more"
              target="_blank"
              rel="noopener noreferrer"
              href={getCustomURL(
                'https://razorpay.com/docs/payment-gateway/refunds/#setting-the-default-speed-of-refunds',
              )}
              onClick={() =>
                analyticsTrack({
                  objectName: 'know more',
                  actionName: 'clicked',
                  screen: 'settings',
                  properties: {
                    location: 'configuration',
                    flowName: 'Default Refund Speed',
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                })
              }
            >
              Know more
              <i className="i i-external-link know-more-label" />
            </a>
            <a
              className="highlight know-more guide-link"
              target="_blank"
              rel="noopener noreferrer"
              href={getCustomURL('https://razorpay.com/docs/payment-gateway/instant-refunds/api')}
              onClick={() =>
                analyticsTrack({
                  objectName: 'api reference guide',
                  actionName: 'clicked',
                  screen: 'settings',
                  properties: {
                    location: 'configuration',
                    flowName: 'Default Refund Speed',
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                })
              }
            >
              API Reference Guide
              <i className="i i-external-link know-more-label" />
            </a>
          </span>
        </div>

        <div className="panel-body refund-speed-pannel">
          <div className="row">
            <div className="col-sm-6 p5">
              <div
                className={`refund-panel-col ${
                  this.state.default_refund_speed == 'normal' ? 'active' : ''
                }`}
              >
                <h4>
                  <b>Normal Refund</b>
                  <input
                    type="radio"
                    className="radio-pointer refund-speed-change-permission"
                    name="default_instant"
                    checked={this.state.default_refund_speed == 'normal'}
                    onChange={(e) => {
                      const speed = e.target.checked ? 'normal' : 'optimum';
                      this.checkDefaultRefundSpeed(speed);
                    }}
                  />
                </h4>
                <p>Your customer will get refunds in 5-7 days.</p>
                <br />
                <span className="refunds-speed-tag">
                  NORMAL SPEED &nbsp;
                  <span>
                    <i className="i i-help" />
                    <Popover align="bottom" theme="dark">
                      <PopoverBody>
                        <div>
                          All your refund API calls will have speed set to `normal` by default
                          unless it is set to `optimum` explicitly
                        </div>
                      </PopoverBody>
                    </Popover>
                  </span>
                </span>
              </div>
            </div>
            {(!isInstantRefundOrg || (isInstantRefundOrg && isInstantRefundMid)) &&
              !isConfigTagEnabled('refunds.instant_refunds') && (
                <div className="col-sm-6 p5">
                  <div
                    className={`refund-panel-col ${
                      this.state.default_refund_speed == 'optimum' ? 'active' : ''
                    }
                  ${feeBearerValue === 'customer' ? 'disabled' : ''}`}
                    id="instant-refund-panel-col"
                  >
                    <h4>
                      <i className="i i-instant-refund" />
                      <b>Instant Refund</b>

                      {!showWhenUtil({
                        featureEnabled: 'disable_instant_refunds',
                      }) ? (
                        feeBearerValue === 'customer' ? (
                          <i className="i i-outline-lock" />
                        ) : (
                          <input
                            type="radio"
                            className="radio-pointer refund-speed-change-permission"
                            checked={this.state.default_refund_speed == 'optimum'}
                            name="default_instant"
                            onChange={(e) => {
                              const speed = e.target.checked ? 'optimum' : 'normal';
                              this.checkDefaultRefundSpeed(speed);
                            }}
                          />
                        )
                      ) : null}
                    </h4>
                    <p>
                      At a{' '}
                      <strong
                        className="pointer"
                        onClick={() => {
                          this.props.tracking.trackEvent(
                            window.rzpQ.merchantActions().initiated(`Click - Minimal Fee`, {
                              label: `Setting Page`,
                              session_id: window.session_id,
                              category: 'Merchant Dashboard - IR',
                            }),
                          );
                          this.props.openModal({
                            component: <InstantRefundFee pricing={this.props.refund_pricing} />,
                            size: 'small',
                          });
                        }}
                      >
                        minimal fee
                      </strong>
                      , your customer will get refunds instantly.
                    </p>

                    <br />
                    {!showWhenUtil({
                      featureEnabled: 'disable_instant_refunds',
                    }) ? (
                      <span className="refunds-speed-tag">
                        OPTIMUM SPEED &nbsp;
                        <span>
                          <i className="i i-help" />
                          <Popover align="bottom" theme="dark">
                            <PopoverBody>
                              <div>
                                All your refund API calls will have speed set to `optimum` by
                                default unless it is set to `normal` explicitly{' '}
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
                          onClick={() => {
                            raiseTicket();
                            window.rzpAnalytics?.({
                              eventCategory: 'Dashboard - Instant Refund',
                              eventAction: 'Contact Support',
                              eventLabel: `Setting Page | Contact Support`,
                            });
                          }}
                          className="highlight know-more contact-support-link"
                        >
                          <strong>contact support</strong>
                        </a>
                      </p>
                    )}
                  </div>
                  {feeBearerValue === 'customer' && (
                    <span className="instant-refund-disabled-text">
                      Locked when convenience fee model is selected
                    </span>
                  )}
                </div>
              )}{' '}
          </div>
        </div>
      </div>
    );
  }
}

export default compose(
  connect(
    (state) => {
      return {
        user: state.session.user,
        refund_pricing: state.config.refund_pricing,
        features: state.config.features,
        default_refund_speed: state.config.config.default_refund_speed,
        lateAuthConfig: state.config.lateAuthConfig,
        org: state.session.org,
      };
    },
    {
      updateFeatures,
      showNotification,
      updateConfig,
      openModal,
      fetchRefundPricing,
      createLateAuthConfig,
      fetchFeatureStatus,
      updateMerchant: updateMerchantReducer,
    },
  ),
  // eslint-disable-next-line babel/new-cap
  RTracking(() => window.rzpQ.component('DefaultRefundSpeed')),
  withI18Service,
)(DefaultRefundSpeed);
