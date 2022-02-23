import { Component } from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import Input from 'common/new-ui/Input';
import TimeInput from './TimeInput';
import { classList, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { parseTimeoutValues, renderTimeoutAsString, capitalize } from './util';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { analyticsTrack } from 'common/utils/analytics';

const refund_options = [
  { label: 'Select Option', name: '' },
  { label: 'Normal Refund', name: 'normal' },
  { label: 'Instant Refund', name: 'optimum' },
];

export default class PaymentsCaptureConfigurationModal extends Component {
  constructor(props) {
    super(props);

    this.state = {
      activeStep: this.props.captureType === 'automatic' ? 1 : 2,
      skipped: false,
      completedSteps: [],
      ...this.getInitialStates(),
    };
  }

  getInitialStates() {
    if (!this.props.lateAuthConfig) return { refundValue: null, automatic: {}, manual: {} };

    const capture_options = this.props.lateAuthConfig.config.capture_options;
    let automatic = {};
    let manual = {};

    if (capture_options.automatic_expiry_period) {
      automatic = parseTimeoutValues(capture_options, 'automatic_expiry_period');
    }

    if (capture_options.manual_expiry_period) {
      manual = parseTimeoutValues(capture_options, 'manual_expiry_period');
    }

    return {
      automatic,
      manual,
      refundValue: capture_options.refund_speed,
    };
  }

  handleAutomaticTimeoutValues = (value, type) => {
    this.setState((prevState) => {
      const _obj = { ...prevState.automatic };
      if (value) _obj[type] = value;
      else delete _obj[type];
      return { automatic: _obj };
    });
  };

  handleManualTimeoutValues = (value, type) => {
    this.setState((prevState) => {
      const _obj = { ...prevState.manual };
      if (value) _obj[type] = value;
      else delete _obj[type];
      return { manual: _obj };
    });
  };

  handleNext = () => {
    this.setState((prevState) => {
      return {
        activeStep: prevState.activeStep + 1,
        completedSteps: [...prevState.completedSteps, prevState.activeStep],
      };
    });

    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Payments Capture Settings',
      eventAction: 'Next',
      eventLabel: 'Entered value | Next',
    });
  };

  handleBack = () => {
    this.setState((prevState) => {
      return {
        activeStep: prevState.activeStep - 1,
        completedSteps: [...prevState.completedSteps.slice(0, prevState.completedSteps.length - 1)],
      };
    });
  };

  handleSkip = () => {
    this.setState((prevState) => {
      return {
        activeStep: prevState.activeStep + 1,
        completedSteps: [...prevState.completedSteps, prevState.activeStep],
        skipped: true,
      };
    });

    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Payments Capture Settings',
      eventAction: 'Skip',
      eventLabel: `${
        Object.keys(this.state.manual).length > 0
          ? 'Entered value | Skip'
          : 'Did not enter value | Skip'
      }`,
    });
  };

  handleSave = () => {
    const { captureType } = this.props;
    const { refundValue } = this.state;

    this.props.createLateAuthConfig(this.state, this.props.captureType);

    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Payments Capture Settings',
      eventAction: 'Save and Close',
      eventLabel: `${capitalize(captureType)} - Select ${capitalize(
        refundValue,
      )} speed - Hover tool tip `,
    });

    analyticsTrack({
      objectName: 'set refund speed',
      actionName: 'clicked',
      screen: 'settings',
      properties: {
        location: 'configuration',
        actionName: 'save & close',
        flowName: `${captureType} capture`,
        refundSpeed: refundValue,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };
  // top: 144px;
  // height: 290px;
  renderStylesWhenActive = () => {
    const _l = Object.keys(this.state.manual).length;

    if (this.props.captureType === 'manual') {
      return { top: '99px', height: _l > 0 ? '295px' : '253px' };
    } else {
      return { top: '166px', height: _l > 0 ? '290px' : '243px' };
    }
  };

  handleDropdownSelection = (e) => this.setState({ refundValue: e.target.value });

  capitalizeFirstLetter = (string) => string.charAt(0).toUpperCase() + string.slice(1);

  onTooltipHover = () => {
    window.rzpAnalytics?.({
      eventCategory: 'Dashboard - Payments Capture Settings',
      eventAction: 'Save and Close',
      eventLabel: `${capitalize('captureType')} - Select ${capitalize(
        'refundValue',
      )} speed - Hover tool tip `,
    });
  };

  getContentHeightAutomatic = (_) =>
    this.state.refundValue === 'optimum'
      ? 'full-span-modal-automatic-optimum'
      : 'full-span-modal-automatic-normal';

  getContentHeightManual = (_) =>
    this.state.refundValue === 'optimum'
      ? 'full-span-modal-manual-optimum'
      : 'full-span-modal-manual-normal';

  render() {
    const { captureType } = this.props;

    return (
      <div
        class={classList(
          'payment-capture-configuration-container',
          this.state.activeStep === 3
            ? captureType === 'automatic'
              ? this.getContentHeightAutomatic()
              : this.getContentHeightManual()
            : null,
        )}
      >
        <ModalHeader
          title={`${this.capitalizeFirstLetter(captureType)} Capture`}
          onCloseClick={() => {
            analyticsTrack({
              objectName: 'set automatic capture timeout',
              actionName: 'clicked',
              screen: 'settings',
              properties: {
                location: 'configuration',
                actionName: 'close',
                ...getCommonAnalyticsProperties(window.rzp_user),
              },
            });
            this.props.closeModal();
          }}
        />
        <div class="configuration-content">
          {captureType === 'automatic' && (
            <div class="custom-timeout-subtitle">
              Setup Custom Timeout{' '}
              <strong
                style={{ color: '#2B83EA' }}
                onClick={() => {
                  this.props.onChangeClick('automatic');
                }}
              >
                Change
              </strong>
            </div>
          )}
          <div
            class={classList(
              'configuration-row__automatic',
              this.state.activeStep === 1 ? 'highlight' : '',
            )}
            style={{ marginTop: '20px' }}
          >
            {this.state.activeStep === 1 && captureType === 'automatic' ? (
              <>
                <div class="content-header">Set Automatic Capture Timeout</div>
                <div class="vertical-connector" style={{ top: '121px', height: '170px' }} />
                <div class="content-subtitle">Capture all payments authorised within</div>
                <div class="content-container">
                  <TimeInput
                    handleValueChange={this.handleAutomaticTimeoutValues}
                    values={this.state.automatic}
                  />
                  <p>Enter value between 12 mins and 5 days</p>
                </div>
                <div class="content-action">
                  <button
                    class="btn btn-primary"
                    onClick={() => {
                      analyticsTrack({
                        objectName: 'set automatic capture timeout',
                        actionName: 'clicked',
                        screen: 'settings',
                        properties: {
                          location: 'configuration',
                          actionName: 'next',
                          timeoutPeriod: this.state.automatic,
                          ...getCommonAnalyticsProperties(window.rzp_user),
                        },
                      });
                      this.handleNext();
                    }}
                    disabled={!(Object.keys(this.state.automatic).length > 0)}
                  >
                    Next
                  </button>
                </div>
              </>
            ) : (
              captureType === 'automatic' && (
                <>
                  <div
                    class={classList(
                      'content-header',
                      this.state.completedSteps.includes(1) ? 'content-filled' : '',
                    )}
                  >
                    Set Automatic Capture Timeout
                  </div>
                  <div class="vertical-connector" style={{ top: '122px' }} />
                </>
              )
            )}
          </div>
          <div
            class={classList(
              'configuration-row__manual',
              this.state.activeStep === 2 ? 'highlight' : '',
            )}
          >
            {this.state.activeStep === 2 ? (
              <>
                <div class="content-header">
                  Add Manual Capture Timeout
                  {captureType === 'automatic' && (
                    <button
                      class="btn btn-xs btn-default"
                      onClick={() => {
                        analyticsTrack({
                          objectName: 'add manual capture timeout',
                          actionName: 'clicked',
                          screen: 'settings',
                          properties: {
                            location: 'configuration',
                            actionName: 'skip',
                            flowName: `${captureType} capture`,
                            ...getCommonAnalyticsProperties(window.rzp_user),
                          },
                        });
                        this.handleSkip();
                      }}
                    >
                      Skip
                    </button>
                  )}
                </div>
                <div class="vertical-connector" style={this.renderStylesWhenActive()} />
                <p class="help-text">
                  Payments authorised after 60 minutes can be captured manually. Till what time do
                  you want to capture payments manually?
                </p>
                <div class="content-subtitle">Manually capture all payments authorised within</div>
                <div class="content-container">
                  <TimeInput
                    handleValueChange={this.handleManualTimeoutValues}
                    values={this.state.manual}
                  />
                  <p>Enter value between 12 mins and 5 days</p>
                </div>
                {Object.keys(this.state.manual).length > 0 && (
                  <div class="info-text">
                    <div />
                    <p>
                      Payments authorised after{' '}
                      <strong>{renderTimeoutAsString(this.state.manual)}</strong> will be refunded.
                    </p>
                  </div>
                )}
                <div class="content-action">
                  {captureType === 'automatic' && (
                    <button
                      class="btn btn-default"
                      onClick={() => {
                        analyticsTrack({
                          objectName: 'add manual capture timeout',
                          actionName: 'clicked',
                          screen: 'settings',
                          properties: {
                            location: 'configuration',
                            actionName: 'back',
                            flowName: `${captureType} capture`,
                            ...getCommonAnalyticsProperties(window.rzp_user),
                          },
                        });
                        this.handleBack();
                      }}
                    >
                      Back
                    </button>
                  )}
                  <button
                    class="btn btn-primary"
                    onClick={() => {
                      analyticsTrack({
                        objectName: 'add manual capture timeout',
                        actionName: 'clicked',
                        screen: 'settings',
                        properties: {
                          location: 'configuration',
                          actionName: 'next',
                          flowName: `${captureType} capture`,
                          timeoutPeriod: this.state.manual,
                          ...getCommonAnalyticsProperties(window.rzp_user),
                        },
                      });
                      this.handleNext();
                    }}
                    disabled={!(Object.keys(this.state.manual).length > 0)}
                  >
                    Next
                  </button>
                </div>
              </>
            ) : (
              <>
                <div
                  class={classList(
                    'content-header',
                    this.state.completedSteps.includes(2) ? 'content-filled' : '',
                  )}
                >
                  Add Manual Capture Timeout
                </div>
                <div
                  class="vertical-connector"
                  style={{
                    top:
                      this.state.activeStep === 3
                        ? captureType === 'manual'
                          ? '100px'
                          : '167px'
                        : '305px',
                  }}
                />
              </>
            )}
          </div>
          <div
            class={classList(
              'configuration-row__refund',
              this.state.activeStep === 3 ? 'highlight' : '',
            )}
          >
            {this.state.activeStep === 3 ? (
              <>
                <div class="content-header">Set Refund Speed</div>
                <p class="help-text">
                  All payments that are not captured within manual timeout period will be refunded
                  to you customers. Select a refund speed.
                </p>
                <div class="content-subtitle">Refund Speed</div>
                <div class="content-container">
                  <Input.Select
                    options={refund_options}
                    onChange={this.handleDropdownSelection}
                    defaultValue={this.state.refundValue}
                  />
                  {this.state.refundValue === 'optimum' && (
                    <p>
                      Late authorised payments would be refunded instantly
                      <i
                        class="i i-info-circle"
                        style={{ paddingLeft: '5px' }}
                        onMouseEnter={this.onTooltipHover}
                      >
                        <Popover align="bottom" theme="dark">
                          <PopoverBody>
                            <div style={{ fontStyle: 'normal' }}>
                              A minimal fee would be charged for payments refunded instantly.
                              Currently supported for UPI, netbanking and select credit cards only.
                              Normal speed will apply on other payment methods.
                            </div>
                          </PopoverBody>
                        </Popover>
                      </i>
                    </p>
                  )}
                </div>
                <div class="content-action">
                  <button
                    class="btn btn-default"
                    onClick={() => {
                      analyticsTrack({
                        objectName: 'set refund speed',
                        actionName: 'clicked',
                        screen: 'settings',
                        properties: {
                          location: 'configuration',
                          actionName: 'back',
                          flowName: `${captureType} capture`,
                          ...getCommonAnalyticsProperties(window.rzp_user),
                        },
                      });
                      this.handleBack();
                    }}
                  >
                    Back
                  </button>
                  <button
                    class="btn btn-primary"
                    disabled={!this.state.refundValue}
                    onClick={this.handleSave}
                  >
                    Save & Close
                  </button>
                </div>
              </>
            ) : (
              <div
                class="content-header"
                style={{
                  paddingBottom: this.state.activeStep !== 3 ? '20px' : '',
                }}
              >
                Set Refund Speed
              </div>
            )}
          </div>
        </div>
      </div>
    );
  }
}
