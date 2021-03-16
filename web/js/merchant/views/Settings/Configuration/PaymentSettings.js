import * as ModalActions from 'merchant_common/reducers/modals';
import CaptureSettingsModal from 'merchant/views/Settings/Configuration/PaymentCaptureComponents/CaptureSettingsModal';
import AutomaticCaptureModal from 'merchant/views/Settings/Configuration/PaymentCaptureComponents/AutomaticCaptureModal';
import PaymentsCaptureConfigurationModal from 'merchant/views/Settings/Configuration/PaymentCaptureComponents/PaymentsCaptureConfigurationModal';
import Timeouts from 'merchant/views/Settings/Configuration/PaymentCaptureComponents/Timeouts';
import moment from 'moment';

import { Component } from 'react';
import { connect } from 'react-redux';
import {
  updateFeatures,
  fetchLateAuthConfig,
  createLateAuthConfig,
} from 'merchant/reducers/config';
import { showNotification } from 'merchant_common/reducers/notifications';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

@connect(
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
    showNotification,
    ...ModalActions,
  },
)
export default class PaymentSettings extends Component {
  constructor(props) {
    super(props);
    this.state = {
      isToggleActive: false,
    };
  }

  componentDidMount() {
    this.props.fetchLateAuthConfig();
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.lateAuthConfig.error) {
      this.props.showNotification({
        type: 'error',
        message: `Couldn't fetch payment capture settings`,
      });
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
      error,
    } = this.props.lateAuthConfig;
    this.props.closeModal();

    let method = '';

    let payload = {
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

        window.rzpAnalytics({
          eventCategory: 'Dashboard - Payments Capture Settings',
          eventAction: 'Automatic Timeout',
          eventLabel: 'Setting Both Automatic & Manual Timeouts',
          timeoutValue: `Automatic - ${automaticTimeoutValue} - Manual - ${manualTimeoutValue}`,
        });
      } else {
        window.rzpAnalytics({
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

      window.rzpAnalytics({
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

    if (obj['days']) {
      let daysInMinutes = moment.duration(parseInt(obj['days']), 'days').asMinutes();
      timeoutValue = timeoutValue + daysInMinutes;
    }

    if (obj['hrs']) {
      let hrsInMinutes = moment.duration(parseInt(obj['hrs']), 'hours').asMinutes();
      timeoutValue = timeoutValue + hrsInMinutes;
    }

    if (obj['mins']) {
      timeoutValue = timeoutValue + parseInt(obj['mins']);
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
    window.rzpAnalytics({
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
      window.rzpAnalytics({
        eventCategory: 'Dashboard - Payments Capture Settings',
        eventAction: 'Done',
        eventLabel: 'Configure now - Capture Settings - Automatic Capture - Done',
      });
    } else {
      this.handleManualCaptureClick();
      window.rzpAnalytics({
        eventCategory: 'Dashboard - Payments Capture Settings',
        eventAction: 'Done',
        eventLabel: 'Configure now - Capture Settings - Manual Capture - Done',
      });
    }
  };

  automaticCaptureDone = (authType, configureTime) => {
    if (configureTime) {
      // open configuration modal
      authType === 'automatic'
        ? this.handleAutomaticCaptureClick()
        : this.handleManualCaptureClick();

      window.rzpAnalytics({
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

      window.rzpAnalytics({
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
    window.rzpAnalytics({
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
    this.setState((prevState) => {
      return {
        isToggleActive: !prevState.isToggleActive,
      };
    });
  };

  isConfigDefault = (configOptions) => {
    if (!configOptions.automatic_expiry_period && !configOptions.manual_expiry_period) return true;
    else return false;
  };

  render() {
    const {
      data: { items },
      error,
    } = this.props.lateAuthConfig;
    const { isToggleActive } = this.state;
    const { role } = this.props.user;

    if (error || !items) {
      return null;
    }

    // If config exists and is as per old schema - return null
    if (items[0]) {
      if (items[0].config.capture && items[0].config.refund) {
        return null;
      }
    }

    return (
      <div class="panel panel-default">
        <div class="panel-heading">
          <span class="title">Payment Capture</span>

          <span class="toggler-btn">
            <a
              href="https://razorpay.com/docs/payment-gateway/payments/capture-settings/"
              target="_blank"
              rel="noreferrer"
              onClick={() =>
                analyticsTrack({
                  objectName: 'know more',
                  actionName: 'clicked',
                  screen: 'settings',
                  properties: {
                    location: 'configuration',
                    flowName: 'payment capture',
                    ...getCommonAnalyticsProperties(window.rzp_user),
                  },
                })
              }
            >
              Know More <i class="i i-external-link" style={{ marginLeft: '5px' }} />
            </a>
          </span>
        </div>

        <div class="panel-body payment-capture-panel">
          {items.length !== 0 && (
            <div class="payment-capture-panel-row">
              <p style={{ paddingBottom: '15px' }}>
                <strong>
                  Capture settings are applicable only if Orders API is used to create the payment.
                  Capture values passed in the{' '}
                  <a
                    style={{ paddingRight: '2px' }}
                    href="https://razorpay.com/docs/api/orders"
                    target="_blank"
                    rel="noreferrer"
                  >
                    Orders API
                  </a>
                  will override these settings if there is any conflict.
                </strong>
              </p>
              <div class="panel-content">
                <div
                  class={`left-panel ${items[0].config.capture === 'automatic' ? 'is-active' : ''}`}
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
                          window.rzpAnalytics({
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
                          <i className={'i i-chevron-' + (isToggleActive ? 'up' : 'down')} />
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
                  class={`right-panel ${items[0].config.capture === 'manual' ? 'is-active' : ''}`}
                  style={{
                    marginLeft: '5px',
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
                          window.rzpAnalytics({
                            eventCategory: 'Dashboard - Payments Capture Settings',
                            eventAction: 'Configure Now',
                            eventLabel: 'Configure now - Manual Capture - Returning User',
                          });
                        }}
                      />
                    )}
                  </div>
                  <div class="panel-description">
                    <p>Capture the payments manually</p>
                  </div>
                  {items[0].config.capture === 'manual' &&
                    !this.isConfigDefault(items[0].config.capture_options) && (
                      <div style={{ paddingLeft: '15px' }}>
                        <div
                          class="text-primary timeoutview-toggler"
                          onClick={this.handleContentToggle}
                        >
                          Timeouts{' '}
                          <i className={'i i-chevron-' + (isToggleActive ? 'up' : 'down')} />
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
            </div>
          )}
          {items.length === 0 && (
            <>
              <div class="description">
                <strong>
                  Capture settings are applicable only if Orders API is used to create the payment.
                  Capture values passed in the{' '}
                  <a
                    style={{ paddingRight: '2px' }}
                    href="https://razorpay.com/docs/api/orders"
                    target="_blank"
                    rel="noreferrer"
                  >
                    Orders API
                  </a>
                  will override these settings if there is any conflict.
                </strong>
              </div>
              <p>
                Payments must be captured once they are authorized. If not, they are auto-refunded.
                Use the default capture and auto-refund settings to better control this.
              </p>
              <button
                class="btn btn-primary"
                onClick={this.configureNow}
                style={{ marginTop: '15px' }}
                disabled={role !== 'owner' ? true : false}
              >
                Configure Now
              </button>
            </>
          )}
        </div>
      </div>
    );
  }
}
