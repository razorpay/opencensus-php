import { Component } from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import Input from 'common/new-ui/Input';
import TimeInput from './TimeInput';
import { classList } from 'common/utils/rzp-utils';

const refund_options = [
  { label: 'Select Option', name: '' },
  { label: 'Normal Refund', name: 'Normal Refund' },
  { label: 'Instant Refund', name: 'Instant Refund' },
];

export default class PaymentsCaptureConfigurationModal extends Component {
  constructor(props) {
    super(props);

    this.state = {
      activeStep: this.props.captureType === 'automatic' ? 1 : 2,
      completedSteps: [],
      refundValue: null,
      automatic: {},
      manual: {},
    };
  }

  handleAutomaticTimeoutValues = (value, type) => {
    let _obj = { ...this.state.automatic };
    if (value) _obj[type] = value;
    else delete _obj[type];
    this.setState({ automatic: _obj });
  };

  handleManualTimeoutValues = (value, type) => {
    let _obj = { ...this.state.manual };
    if (value) _obj[type] = value;
    else delete _obj[type];
    this.setState({ manual: _obj });
  };

  handleNext = () => {
    this.setState(prevState => {
      return {
        activeStep: prevState.activeStep + 1,
        completedSteps: [...prevState.completedSteps, prevState.activeStep],
      };
    });
  };

  handleBack = () => {
    this.setState(prevState => {
      return {
        activeStep: prevState.activeStep - 1,
        completedSteps: [
          ...prevState.completedSteps.slice(
            0,
            prevState.completedSteps.length - 1
          ),
        ],
      };
    });
  };

  handleSave = () =>
    this.props.createLateAuthConfig(this.state, this.props.captureType);

  renderStylesWhenActive = () => {
    let _l = Object.keys(this.state.manual).length;

    if (this.props.captureType === 'manual') {
      return { top: '78px', height: _l > 0 ? '301px' : '253px' };
    } else {
      return { top: '124px', height: _l > 0 ? '301px' : '253px' };
    }
  };

  handleDropdownSelection = e => this.setState({ refundValue: e.target.value });

  renderManualTimeout = () => {
    const _strings = Object.keys(this.state.manual).map(key => {
      return `${this.state.manual[key]} ${key}`;
    });

    return _strings.join(' and ');
  };

  render() {
    const { captureType } = this.props;

    return (
      <div
        class={classList(
          'payment-capture-configuration-container',
          this.state.activeStep === 3 ? 'full-span-modal' : ''
        )}
      >
        <ModalHeader
          title="Automatic Capture"
          onCloseClick={() => {
            this.props.closeModal();
          }}
        />
        <div class="configuration-content">
          <div
            class={classList(
              'configuration-row__automatic',
              this.state.activeStep === 1 ? 'highlight' : ''
            )}
          >
            {this.state.activeStep === 1 && captureType === 'automatic' ? (
              <>
                <div class="content-header">Set Automatic Capture Timeout</div>
                <div
                  class="vertical-connector"
                  style={{ top: '79px', height: '182px' }}
                />
                <div class="content-subtitle">
                  Capture all payments authorised within
                </div>
                <div class="content-container">
                  <TimeInput
                    handleValueChange={this.handleAutomaticTimeoutValues}
                    values={this.state.automatic}
                  />
                  <p>Enter value between 30 mins and 5 days</p>
                </div>
                <div class="content-action">
                  <button
                    class="btn btn-primary"
                    onClick={this.handleNext}
                    disabled={
                      Object.keys(this.state.automatic).length > 0
                        ? false
                        : true
                    }
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
                      this.state.completedSteps.includes(1)
                        ? 'content-filled'
                        : ''
                    )}
                  >
                    Set Automatic Capture Timeout
                  </div>
                  <div class="vertical-connector" style={{ top: '80px' }} />
                </>
              )
            )}
          </div>
          <div
            class={classList(
              'configuration-row__manual',
              this.state.activeStep === 2 ? 'highlight' : ''
            )}
          >
            {this.state.activeStep === 2 ? (
              <>
                <div class="content-header">
                  Add Manual Capture Timeout
                  {captureType === 'automatic' && (
                    <button
                      class="btn btn-xs btn-default"
                      onClick={this.handleNext}
                    >
                      Skip
                    </button>
                  )}
                </div>
                <div
                  class="vertical-connector"
                  style={this.renderStylesWhenActive()}
                />
                <p class="help-text">
                  Payments authorised after 60 minutes can be captured manually.
                  Till what time do you want to capture payments manually?
                </p>
                <div class="content-subtitle">
                  Manually capture all payments authorised within
                </div>
                <div class="content-container">
                  <TimeInput
                    handleValueChange={this.handleManualTimeoutValues}
                    values={this.state.manual}
                  />
                  <p>Enter value between 60 mins and 5 days</p>
                </div>
                {Object.keys(this.state.manual).length > 0 && (
                  <div class="info-text">
                    <div />
                    <p>
                      Payments authorised after{' '}
                      <strong>{this.renderManualTimeout()}</strong> will be
                      refunded.
                    </p>
                  </div>
                )}
                <div class="content-action">
                  {captureType === 'automatic' && (
                    <button class="btn btn-default" onClick={this.handleBack}>
                      Back
                    </button>
                  )}
                  <button
                    class="btn btn-primary"
                    onClick={this.handleNext}
                    disabled={
                      Object.keys(this.state.manual).length > 0 ? false : true
                    }
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
                    this.state.completedSteps.includes(2)
                      ? 'content-filled'
                      : ''
                  )}
                >
                  Add Manual Capture Timeout
                </div>
                <div
                  class="vertical-connector"
                  style={{
                    top:
                      this.state.activeStep === 3
                        ? captureType === 'manual' ? '80px' : '125px'
                        : '272px',
                  }}
                />
              </>
            )}
          </div>
          <div
            class={classList(
              'configuration-row__refund',
              this.state.activeStep === 3 ? 'highlight' : ''
            )}
          >
            {this.state.activeStep === 3 ? (
              <>
                <div class="content-header">Set Refund Speed</div>
                <p class="help-text">
                  All payments that are not captured within manual timeout
                  period will be refunded to you customers. Select a refund
                  speed.
                </p>
                <div class="content-subtitle">Refund Speed</div>
                <div class="content-container">
                  <Input.Select
                    options={refund_options}
                    onChange={this.handleDropdownSelection}
                  />
                </div>
                <div class="content-action">
                  <button class="btn btn-default" onClick={this.handleBack}>
                    Back
                  </button>
                  <button
                    class="btn btn-primary"
                    disabled={this.state.refundValue ? false : true}
                    onClick={this.handleSave}
                  >
                    Save & Close
                  </button>
                </div>
              </>
            ) : (
              <div class="content-header">Set Refund Speed</div>
            )}
          </div>
        </div>
      </div>
    );
  }
}
