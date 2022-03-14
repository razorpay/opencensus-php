import React, { Component } from 'react';
import * as ModalActions from 'merchant_common/reducers/modals';
import CaptureSettingsModal from 'merchant/views/Settings/Configuration/PaymentCaptureComponents/CaptureSettingsModal';
import AutomaticCaptureModal from 'merchant/views/Settings/Configuration/PaymentCaptureComponents/AutomaticCaptureModal';
import PaymentsCaptureConfigurationModal from 'merchant/views/Settings/Configuration/PaymentCaptureComponents/PaymentsCaptureConfigurationModal';
import Timeouts from 'merchant/views/Settings/Configuration/PaymentCaptureComponents/Timeouts';
import moment from 'moment';
import { connect } from 'react-redux';
import {
  updateFeatures,
  fetchLateAuthConfig,
  createLateAuthConfig,
} from 'merchant/reducers/config';
import { DocLink, getCustomURL } from 'merchant/components/DocsLink';
import { showNotification } from 'merchant_common/reducers/notifications';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import CaptureMode from './CaptureSettingsComponents/CaptureMode';
import rolesList from 'merchant/helpers/permissions/roles-list';

import { parseTimeoutValues } from './CaptureSettingsComponents/data';
import { renderTimeoutAsString } from './PaymentCaptureComponents/util';
import { CAPTURE_SETTINGS } from './deeplink-constants';
import TextHighlighter from 'common/ui/TextHighlighter';
const CAPTURE_ASSETS_CDN_URL = 'https://cdn.razorpay.com/static/assets/capture-settings';

const CAPTURE_DETAILS = [
  {
    imgSrc: `${CAPTURE_ASSETS_CDN_URL}/capture_payment_complete.svg`,
    detail: () => (
      <>
        Customer <b>initiates</b> the payment on your app/website/page. Payment is <b>CREATED</b>
      </>
    ),
  },
  {
    imgSrc: `${CAPTURE_ASSETS_CDN_URL}/capture_customer_amount.svg`,
    detail: () => (
      <>
        Bank <b>AUTHORISES</b> the payment. Amount gets deducted from your customers account.
        <br />
        Sometimes banks can take upto days to authorise a payment
      </>
    ),
  },
  {
    imgSrc: `${CAPTURE_ASSETS_CDN_URL}/capture_bank_auth.svg`,
    detail: () => (
      <>
        You can decide what happens to a payment when bank is Late. These are called{' '}
        <b>LATE AUTHORISED</b> payments.
      </>
    ),
  },
  {
    imgSrc: `${CAPTURE_ASSETS_CDN_URL}/auto_capture.svg`,
    detail: () => (
      <>
        You need to <b>CAPTURE</b> the payment so we can settle it to your account. <br />
        <b>Capture payment Automatically or Manually</b>
      </>
    ),
  },
];

class PaymentSettings extends Component {
  constructor(props) {
    super(props);
    this.state = {
      isToggleActive: false,
      isDetailsToggleActive: false,
    };
  }

  componentDidMount() {
    this.props.fetchLateAuthConfig();
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (nextProps.lateAuthConfig.error) {
      this.props.showNotification({
        type: 'error',
        message: `Couldn't fetch payment capture settings`,
      });
    }

    if (nextProps.lateAuthConfig.data && nextProps.lateAuthConfig.data.length === 0) {
      this.setState({ isDetailsToggleActive: true });
    }

    if (nextProps.createdLateAuthConfig.error) {
      this.props.showNotification({
        type: 'error',
        message: `${nextProps.createdLateAuthConfig.error[0]}`,
      });
    }
  }

  handleCreateLateAuthConfig = (body, authType) => {
    const {
      data: { items },
    } = this.props.lateAuthConfig;
    this.props.closeModal();

    let method = '';

    const payload = {
      type: 'late_auth',
      config: { capture: authType, capture_options: {} },
    };

    if (items.length !== 0) {
      method = 'patch';
    } else {
      method = 'post';
      payload.name = `late_auth_${this.props.user.id}`;
      payload.is_default = true;
    }

    // For capture type automatic
    if (authType === 'automatic') {
      const automaticTimeoutValue = this.getTimeoutValue(body.automatic);
      payload.config.capture_options.automatic_expiry_period = automaticTimeoutValue;

      // Sending manual if not skipped
      if (!body.skipped && body.manual) {
        const manualTimeoutValue = this.getTimeoutValue(body.manual);
        payload.config.capture_options.manual_expiry_period = manualTimeoutValue;

        window.rzpAnalytics?.({
          eventCategory: 'Dashboard - Payments Capture Settings',
          eventAction: 'Automatic Timeout',
          eventLabel: 'Setting Both Automatic & Manual Timeouts',
          timeoutValue: `Automatic - ${automaticTimeoutValue} - Manual - ${manualTimeoutValue}`,
        });
      } else {
        window.rzpAnalytics?.({
          eventCategory: 'Dashboard - Payments Capture Settings',
          eventAction: 'Automatic Timeout',
          eventLabel: 'Setting Only Automatic Timeouts',
          timeoutValue: `Automatic - ${automaticTimeoutValue}`,
        });
      }
    } else {
      // For capture type manual
      const timeoutValue = this.getTimeoutValue(body.manual);
      payload.config.capture_options.manual_expiry_period = timeoutValue;

      window.rzpAnalytics?.({
        eventCategory: 'Dashboard - Payments Capture Settings',
        eventAction: 'Manual Timeout',
        eventLabel: 'Setting Timeout Value',
        timeoutValue: `${timeoutValue}`,
      });
    }

    payload.config.capture_options.refund_speed = body.refundValue;

    if (payload.config.capture_options.automatic_expiry_period === null)
      delete payload.config.capture_options.automatic_expiry_period;

    if (payload.config.capture_options.manual_expiry_period === null)
      delete payload.config.capture_options.manual_expiry_period;

    this.props
      .createLateAuthConfig(payload, method)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: this.state.doesConfigExist ? 'Preference Updated' : 'Preference Saved',
        });

        analyticsTrack({
          objectName: `${authType} capture`,
          actionName: 'result',
          screen: 'settings',
          properties: {
            location: 'configuration',
            status: 'Success',
            automaticTimeoutPeriod: body.automatic,
            manualCapturePeriod: body.manual,
            refundSpeed: payload.config.capture_options.refund_speed,
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });

        this.props.fetchLateAuthConfig();
        this.setState({
          isToggleActive: false,
        });
      })
      .catch((err) => {
        analyticsTrack({
          objectName: `${authType} capture`,
          actionName: 'result',
          screen: 'settings',
          properties: {
            location: 'configuration',
            status: 'Failure',
            failureReason: err.errors[0],
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      });
  };

  getTimeoutValue = (obj) => {
    let timeoutValue = 0;

    if (obj.days) {
      const daysInMinutes = moment.duration(parseInt(obj.days, 10), 'days').asMinutes();
      timeoutValue = timeoutValue + daysInMinutes;
    }

    if (obj.hrs) {
      const hrsInMinutes = moment.duration(parseInt(obj.hrs, 10), 'hours').asMinutes();
      timeoutValue = timeoutValue + hrsInMinutes;
    }

    if (obj.mins) {
      timeoutValue = timeoutValue + parseInt(obj.mins, 10);
    }

    return timeoutValue;
  };

  configureNow = () => {
    this.props.openModal({
      size: 'small',
      component: (
        <CaptureSettingsModal
          closeModal={this.props.closeModal}
          handleDone={this.handleCaptureInitiationDone}
        />
      ),
    });
    triggerHotjarRecording(`Capture_Setting`);
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Payments Capture Settings',
      eventAction: 'Configure Now',
      eventLabel: 'Configure now - Onboarding - New User',
    });
  };

  handleCaptureInitiationDone = (selectedLateAuthType) => {
    analyticsTrack({
      objectName: `${selectedLateAuthType} capture`,
      actionName: 'clicked',
      screen: 'settings',
      properties: {
        location: 'configuration',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    // New flow kicks in based off on rzpX experiment
    if (this.props.user.iscaptureSettingsRevampEnabled) {
      return this.changeSettings();
    }

    // Older flow
    this.props.closeModal();
    if (selectedLateAuthType === 'automatic') {
      this.props.openModal({
        size: 'small',
        component: (
          <AutomaticCaptureModal
            closeModal={this.props.closeModal}
            handleDone={this.automaticCaptureDone}
            handleBack={this.automaticCaptureBack}
            selectedLateAuthType={selectedLateAuthType}
          />
        ),
      });
      return window.rzpAnalytics?.({
        eventCategory: 'Dashboard - Payments Capture Settings',
        eventAction: 'Done',
        eventLabel: 'Configure now - Capture Settings - Automatic Capture - Done',
      });
    }
    this.handleManualCaptureClick();
    return window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Payments Capture Settings',
      eventAction: 'Done',
      eventLabel: 'Configure now - Capture Settings - Manual Capture - Done',
    });
  };

  automaticCaptureDone = (authType, configureTime) => {
    if (configureTime) {
      // open configuration modal
      if (authType === 'automatic') this.handleAutomaticCaptureClick();
      else this.handleManualCaptureClick();

      window.rzpAnalytics?.({
        eventCategory: 'Dashboard - Payments Capture Settings',
        eventAction: 'Done',
        eventLabel: 'Configure now - Automatic capture - Set Custom Timeout - Done',
      });

      analyticsTrack({
        objectName: 'automatic capture popup',
        actionName: 'clicked',
        screen: 'settings',
        properties: {
          location: 'configuration',
          actionName: 'done',
          option: 'set custom timeout',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    } else {
      // set default config for that auth-type
      // make api call here
      this.handleCreateLateAuthConfig(
        {
          refundValue: this.props.default_refund_speed,
          automatic: { days: 5 },
          skipped: false,
        },
        authType,
      );

      window.rzpAnalytics?.({
        eventCategory: 'Dashboard - Payments Capture Settings',
        eventAction: 'Done',
        eventLabel: 'Configure now - Automatic capture - Capture payments automatically - Done',
      });

      analyticsTrack({
        objectName: 'automatic capture popup',
        actionName: 'clicked',
        screen: 'settings',
        properties: {
          location: 'configuration',
          actionName: 'done',
          option: 'capture payments automatically',
          ...getCommonAnalyticsProperties(window.rzp_user),
        },
      });
    }
  };

  automaticCaptureBack = () => {
    this.props.closeModal();
    this.configureNow();
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Payments Capture Settings',
      eventAction: 'Back',
      eventLabel: 'Configure now - Automatic Capture - Back',
    });
    analyticsTrack({
      objectName: 'automatic capture popup',
      actionName: 'clicked',
      screen: 'settings',
      properties: {
        location: 'configuration',
        actionName: 'back',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  handleAutomaticCaptureClick = () => {
    this.props.openModal({
      size: 'small',
      component: (
        <PaymentsCaptureConfigurationModal
          captureType="automatic"
          closeModal={this.props.closeModal}
          createLateAuthConfig={this.handleCreateLateAuthConfig}
          lateAuthConfig={this.props.lateAuthConfig.data.items[0]}
          onChangeClick={this.handleCaptureInitiationDone}
        />
      ),
    });
  };

  handleManualCaptureClick = () => {
    this.props.openModal({
      size: 'small',
      component: (
        <PaymentsCaptureConfigurationModal
          captureType="manual"
          closeModal={this.props.closeModal}
          createLateAuthConfig={this.handleCreateLateAuthConfig}
          lateAuthConfig={this.props.lateAuthConfig.data.items[0]}
        />
      ),
    });
  };

  computeHeight = (captureType, panelType) => {
    if (captureType === panelType && this.state.isToggleActive === true) {
      return '205px';
    } else {
      return '100px';
    }
  };

  handleContentToggle = (_) => {
    if (this.props.user.iscaptureSettingsRevampEnabled && this.state.isToggleActive === false) {
      window.rzpAnalytics?.({
        eventCategory: 'Dashboard - Payments Capture Settings v2',
        eventAction: 'Show Settings Details',
        eventLabel: 'Settings Details',
      });
    }

    this.setState((prevState) => {
      return {
        isToggleActive: !prevState.isToggleActive,
      };
    });
  };

  handleDetailsToggle = () => {
    if (
      this.props.user.iscaptureSettingsRevampEnabled &&
      this.state.isDetailsToggleActive === false
    ) {
      window.rzpAnalytics?.({
        eventCategory: 'Dashboard - Payments Capture Settings v2',
        eventAction: 'Show Details',
        eventLabel: 'Home page | Show Details',
      });
    }

    this.setState((prevState) => ({
      isDetailsToggleActive: !prevState.isDetailsToggleActive,
    }));
  };

  isConfigDefault = (configOptions) => {
    if (!configOptions.automatic_expiry_period && !configOptions.manual_expiry_period) return true;
    else return false;
  };

  changeSettings = () => {
    triggerHotjarRecording(`Capture_Setting`);
    this.props.openModal({
      size: 'medium',
      component: <CaptureMode />,
    });
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Payments Capture Settings v2',
      eventAction: 'Change',
      eventLabel: 'Change',
    });
  };

  onKnowMoreClick = () => {
    analyticsTrack({
      objectName: 'know more',
      actionName: 'clicked',
      screen: 'settings',
      properties: {
        location: 'configuration',
        flowName: 'payment capture',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Payments Capture Settings v2',
      eventAction: 'Know More',
      eventLabel: 'Know More',
    });
  };

  render() {
    const {
      data: { items },
      error,
    } = this.props.lateAuthConfig;
    const { isToggleActive, isDetailsToggleActive } = this.state;
    const { role } = this.props.user;
    const {
      org: { custom_code },
    } = this.props;

    if (error || !items) {
      return null;
    }

    // If config exists and is as per old schema - return null
    if (items[0]) {
      if (items[0].config.capture && items[0].config.refund) {
        return null;
      }
    }
    const { user } = this.props;

    if (user.iscaptureSettingsRevampEnabled) {
      if (items.length === 0) {
        items.push({
          config: {
            capture: 'automatic',
            capture_options: {
              refund_speed: 'normal',
              manual_expiry_period: null,
              automatic_expiry_period: 7200,
            },
          },
        });
      }
    }

    return (
      <div class="panel panel-default">
        <div class="panel-heading">
          <span class="title">
            <TextHighlighter hashedWith={CAPTURE_SETTINGS}>Payment Capture</TextHighlighter>
          </span>

          <span class="toggler-btn">
            <DocLink
              href={
                custom_code === 'axis'
                  ? 'https://razorpay.com/payments/payments/capture-settings/'
                  : 'https://razorpay.com/docs/payment-gateway/payments/capture-settings/'
              }
              target="_blank"
              rel="noopener noreferrer"
              onClick={this.onKnowMoreClick}
            >
              Know more <i class="i i-external-link" style={{ marginLeft: '5px' }} />
            </DocLink>
          </span>
        </div>

        {user.iscaptureSettingsRevampEnabled ? (
          <div class="panel-body payment-capture-panel">
            <div class="panel-content capture-panel-content" style={{ flexDirection: 'column' }}>
              <div
                class="left-panel is-active"
                style={{
                  marginRight: '5px',
                  height: this.computeHeight(items[0].config.capture, 'automatic'),
                }}
              >
                <div class="panel-header">
                  <h4>
                    <b>
                      {items[0].config.capture === 'automatic'
                        ? 'Automatic Capture'
                        : 'Manual Capture'}
                    </b>
                    <i className="i i-done" />
                  </h4>
                </div>
                <div class="panel-description">
                  <p>
                    {items[0].config.capture === 'automatic' ? (
                      <>
                        Payments will be captured automatically if authorised by bank within{' '}
                        <b>
                          {renderTimeoutAsString(
                            parseTimeoutValues(
                              items[0].config.capture_options.automatic_expiry_period,
                            ),
                          )}
                        </b>
                      </>
                    ) : (
                      <>
                        Capture the payments via the API or the dashboard authorised within{' '}
                        <b>
                          {renderTimeoutAsString(
                            parseTimeoutValues(
                              items[0].config.capture_options.manual_expiry_period,
                            ),
                          )}
                        </b>
                      </>
                    )}
                  </p>
                  <button
                    class="btn btn-primary capture-change-btn"
                    onClick={this.changeSettings}
                    disabled={
                      [rolesList.OWNER, rolesList.ADMIN, rolesList.MANAGER].indexOf(role) === -1
                    }
                  >
                    Change
                  </button>
                </div>
                <div style={{ paddingLeft: '15px' }}>
                  <div class="text-primary timeoutview-toggler" onClick={this.handleContentToggle}>
                    Authorisation post{' '}
                    {items[0].config.capture === 'automatic'
                      ? renderTimeoutAsString(
                          parseTimeoutValues(
                            items[0].config.capture_options.automatic_expiry_period,
                          ),
                        )
                      : renderTimeoutAsString(
                          parseTimeoutValues(items[0].config.capture_options.manual_expiry_period),
                        )}{' '}
                    <i className={`i i-chevron-${isToggleActive ? 'up' : 'down'}`} />
                  </div>
                  {isToggleActive && (
                    <Timeouts
                      config={items[0].config}
                      onEditTimeoutClick={() => {
                        this.handleCaptureInitiationDone(items[0].config.capture);
                      }}
                      role={role}
                    />
                  )}
                </div>
              </div>
            </div>
            <div className="capture-details">
              <span>What is Capturing Payments?</span>
              <span className="details-toggler" onClick={this.handleDetailsToggle}>
                {isDetailsToggleActive ? 'Hide Details' : 'Show Details'}
                <i className={`i i-chevron-${isDetailsToggleActive ? 'up' : 'down'}`} />
              </span>
              {isDetailsToggleActive && (
                <div className="capture-details--block">
                  <p>
                    There are a few things that happen between a <b>customer making a payment</b>{' '}
                    and the <b>amount making to your account</b>:
                  </p>
                  <div className="capture-details--steps">
                    {CAPTURE_DETAILS.map((item, index) => (
                      <React.Fragment key={index}>
                        <div className="capture-details--each-step">
                          <img className="capture-details--steps-img" src={item.imgSrc} />
                          <span className="capture-details--dot" />
                          <div className="capture-details--step-detail">{item.detail()}</div>
                        </div>
                        {index < CAPTURE_DETAILS.length - 1 && (
                          <div
                            className="capture-details--line"
                            style={index === 1 ? { marginTop: '-60px' } : {}}
                          />
                        )}
                      </React.Fragment>
                    ))}
                  </div>
                </div>
              )}
            </div>
            {user.iscaptureSettingsRevampEnabled && (
              <div class="note__orders-api">
                <p>
                  <strong>Note:</strong> Capture settings are applicable only if{' '}
                  <a
                    style={{ paddingRight: '2px' }}
                    href={getCustomURL('https://razorpay.com/docs/api/orders')}
                    target="_blank"
                    rel="noopener noreferrer"
                  >
                    Orders API
                  </a>{' '}
                  is used to create the payment. Capture values passed in the Orders API will
                  override these settings if there is any conflict.
                </p>
              </div>
            )}
          </div>
        ) : (
          <div class="panel-body payment-capture-panel">
            {items.length !== 0 && (
              <div class="payment-capture-panel-row">
                <p style={{ paddingBottom: '15px' }}>
                  <strong>
                    Capture settings are applicable only if Orders API is used to create the
                    payment. Capture values passed in the{' '}
                    <a
                      style={{ paddingRight: '2px' }}
                      href={getCustomURL('https://razorpay.com/docs/api/orders')}
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      Orders API
                    </a>
                    <span>will override these settings if there is any conflict.</span>
                  </strong>
                </p>
                {!user.iscaptureSettingsRevampEnabled && (
                  <div class="panel-content">
                    <div
                      class={`left-panel ${
                        items[0].config.capture === 'automatic' ? 'is-active' : ''
                      }`}
                      style={{
                        marginRight: '5px',
                        height: this.computeHeight(items[0].config.capture, 'automatic'),
                      }}
                    >
                      <div class="panel-header">
                        <h4>
                          <b>Automatic Capture</b>
                        </h4>
                        {role === 'owner' && (
                          <input
                            type="radio"
                            checked={items[0].config.capture === 'automatic'}
                            onClick={() => {
                              this.handleCaptureInitiationDone('automatic');
                              triggerHotjarRecording(`Capture_Setting`);
                              window.rzpAnalytics?.({
                                eventCategory: 'Dashboard - Payments Capture Settings',
                                eventAction: 'Configure Now',
                                eventLabel: 'Configure now - Automatic Capture - Returning User',
                              });
                            }}
                          />
                        )}
                      </div>
                      <div class="panel-description">
                        <p>Payments will be captured automatically</p>
                      </div>
                      {items[0].config.capture === 'automatic' &&
                        !this.isConfigDefault(items[0].config.capture_options) && (
                          <div style={{ paddingLeft: '15px' }}>
                            <div
                              class="text-primary timeoutview-toggler"
                              onClick={this.handleContentToggle}
                            >
                              Timeouts{' '}
                              <i className={`i i-chevron-${isToggleActive ? 'up' : 'down'}`} />
                            </div>
                            {isToggleActive && (
                              <Timeouts
                                config={items[0].config}
                                onEditTimeoutClick={() => {
                                  this.handleCaptureInitiationDone('automatic');
                                }}
                                role={role}
                              />
                            )}
                          </div>
                        )}
                    </div>

                    <div
                      class={`right-panel ${
                        items[0].config.capture === 'manual' ? 'is-active' : ''
                      }`}
                      style={{
                        height: this.computeHeight(items[0].config.capture, 'manual'),
                      }}
                    >
                      <div class="panel-header">
                        <h4>
                          <b>Manual Capture</b>
                        </h4>
                        {role === 'owner' && (
                          <input
                            type="radio"
                            checked={items[0].config.capture === 'manual'}
                            onClick={() => {
                              this.handleCaptureInitiationDone('manual');
                              triggerHotjarRecording(`Capture_Setting`);
                              window.rzpAnalytics?.({
                                eventCategory: 'Dashboard - Payments Capture Settings',
                                eventAction: 'Configure Now',
                                eventLabel: 'Configure now - Manual Capture - Returning User',
                              });
                            }}
                          />
                        )}
                      </div>
                      <div class="panel-description">
                        <p>You have to manually capture the payments via dashboard or API</p>
                      </div>
                      {items[0].config.capture === 'manual' &&
                        !this.isConfigDefault(items[0].config.capture_options) && (
                          <div style={{ paddingLeft: '15px' }}>
                            <div
                              class="text-primary timeoutview-toggler"
                              onClick={this.handleContentToggle}
                            >
                              Timeouts{' '}
                              <i className={`i i-chevron-${isToggleActive ? 'up' : 'down'}`} />
                            </div>
                            {isToggleActive && (
                              <Timeouts
                                config={items[0].config}
                                onEditTimeoutClick={() => {
                                  this.handleCaptureInitiationDone('manual');
                                }}
                                role={role}
                              />
                            )}
                          </div>
                        )}
                    </div>
                  </div>
                )}
                <button
                  class="btn btn-primary"
                  onClick={
                    user.iscaptureSettingsRevampEnabled
                      ? this.changeSettings
                      : () => {
                          this.handleCaptureInitiationDone(`${items[0].config.capture}`);
                        }
                  }
                  style={{ marginTop: '15px' }}
                  disabled={role !== 'owner'}
                >
                  Change Settings
                </button>
              </div>
            )}
            {items.length === 0 && !user.iscaptureSettingsRevampEnabled && (
              <>
                <div class="description">
                  <strong>
                    Capture settings are applicable only if Orders API is used to create the
                    payment. Capture values passed in the{' '}
                    <a
                      style={{ paddingRight: '2px' }}
                      href={getCustomURL('https://razorpay.com/docs/api/orders')}
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      Orders API
                    </a>
                    <span>will override these settings if there is any conflict.</span>
                  </strong>
                </div>
                <p>
                  Payments must be captured once they are authorized. If not, they are
                  auto-refunded. Use the default capture and auto-refund settings to better control
                  this.
                </p>
                <button
                  class="btn btn-primary"
                  onClick={this.configureNow}
                  style={{ marginTop: '15px' }}
                  disabled={role !== 'owner'}
                >
                  Configure Now
                </button>
              </>
            )}
          </div>
        )}
      </div>
    );
  }
}

export default connect(
  (state) => {
    return {
      user: state.session.user,
      features: state.config.features,
      lateAuthConfig: state.config.lateAuthConfig,
      createdLateAuthConfig: state.config.createdLateAuthConfig,
      default_refund_speed: state.config.config.default_refund_speed,
    };
  },
  {
    updateFeatures,
    showNotification,
    fetchLateAuthConfig,
    createLateAuthConfig,
    ...ModalActions,
  },
)(PaymentSettings);
