import { Component } from 'react';
import { connect } from 'react-redux';
import {
  updateFeatures,
  fetchLateAuthConfig,
  createLateAuthConfig,
} from 'merchant/reducers/config';
import { showNotification } from 'merchant_common/reducers/notifications';
import * as ModalActions from 'merchant_common/reducers/modals';
import CaptureSettingsModal from 'merchant/views/Settings/Configuration/PaymentCaptureComponents/CaptureSettingsModal';
import AutomaticCaptureModal from 'merchant/views/Settings/Configuration/PaymentCaptureComponents/AutomaticCaptureModal';
import PaymentsCaptureConfigurationModal from 'merchant/views/Settings/Configuration/PaymentCaptureComponents/PaymentsCaptureConfigurationModal';
import HorizontalTimeline from 'merchant/views/Settings/Configuration/PaymentCaptureComponents/HorizontalTimeline';

@connect(
  state => {
    return {
      user: state.session.user,
      features: state.config.features,
      lateAuthConfig: state.config.lateAuthConfig,
      createdLateAuthConfig: state.config.createdLateAuthConfig,
    };
  },
  {
    updateFeatures,
    showNotification,
    fetchLateAuthConfig,
    createLateAuthConfig,
    showNotification,
    ...ModalActions,
  }
)
export default class PaymentSettings extends Component {
  constructor(props) {
    super(props);
    this.state = {
      capture_mode: '',
      timeout_action: '',
      authorisation_timeout: '',
      lateConfigExist: null,
      timelineItems: [
        {
          title: 'Payment Creation',
          time: 'T',
          description: 'Payments captured automatically',
        },
        {
          title: 'Auto Capture Timeout',
          time: 'T+60 min',
          description: 'Payments captured manually',
        },
        {
          title: 'Manual Capture Timeout',
          time: 'T+2 days',
          description: 'Payments are refunded with optimum speed',
        },
        {
          title: '5 days',
          time: 'T+5 days',
          description: '',
        },
      ],
    };
  }

  componentDidMount() {
    // this.props.fetchLateAuthConfig();
  }

  componentWillReceiveProps(nextProps) {
    if (
      nextProps.lateAuthConfig.error ||
      Object.keys(nextProps.createdLateAuthConfig.data).length > 0
    )
      return;

    if (nextProps.lateAuthConfig.data.items.length === 0) return;
    else {
      let config = nextProps.lateAuthConfig.data.items[0].config;
      const { capture, refund } = config;
      this.setState({
        authorisation_timeout: capture.timeout_duration.toString(),
        capture_mode: parseInt(capture.value),
        timeout_action: refund ? refund.speed : 'default',
        doesConfigExist: true,
      });
    }
  }

  handleCreateLateAuthConfig = (body, authType) => {
    console.log(body, authType);
    this.props.closeModal();
    this.setState({
      lateConfigExist: authType,
    });
    return;
    let method = '';

    let payload = {
      type: 'late_auth',
      config: { capture: {} },
    };

    // config already exists, edit request - send only type and  //
    if (this.state.doesConfigExist) {
      method = 'patch';
    } else {
      method = 'post';
      payload.name = `late_auth_${this.props.user.id}`;
      payload.is_default = true;
    }

    // timeout action value can be either default or refund, depends on selection //
    payload.config.capture.value = parseInt(this.state.capture_mode);
    payload.config.capture.timeout_duration = parseInt(
      this.state.authorisation_timeout
    );
    payload.config.capture.timeout_action =
      this.state.timeout_action === 'default' ? 'default' : 'refund';

    // If timeout action !== default, add a refund object to payment, otherwise ! //
    if (this.state.timeout_action !== 'default') {
      payload.config.refund = {
        speed: this.state.timeout_action,
      };
    }

    // this.props.createLateAuthConfig(payload, method).then(() => {
    //   this.props.showNotification({
    //     type: 'success',
    //     message: this.state.doesConfigExist
    //       ? 'Config updated successfully'
    //       : 'Config created successfully',
    //   });
    // });
  };

  validateCaptureTimeout = () => {
    if (this.state.authorisation_timeout === '') return;

    if (
      this.state.authorisation_timeout < 30 ||
      this.state.authorisation_timeout > 7200
    )
      return 'red';
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
  };

  handleCaptureInitiationDone = selectedLateAuthType => {
    this.props.closeModal();
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
  };

  automaticCaptureDone = (authType, configureTime) => {
    if (configureTime) {
      // open configuration modal
      authType === 'automatic'
        ? this.handleAutomaticCaptureClick()
        : this.handleManualCaptureClick();
    } else {
      // set default config for that auth-type
      // make api call here
      this.handleCreateLateAuthConfig({}, authType);
    }
  };

  automaticCaptureBack = () => {
    this.props.closeModal();
    this.configureNow();
  };

  handleAutomaticCaptureClick = () => {
    this.props.openModal({
      size: 'small',
      component: (
        <PaymentsCaptureConfigurationModal
          captureType="automatic"
          closeModal={this.props.closeModal}
          createLateAuthConfig={this.handleCreateLateAuthConfig}
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
        />
      ),
    });
  };

  render() {
    return (
      <div class="panel panel-default">
        <div class="panel-heading">
          <span class="title">Payment Capture</span>

          <span class="toggler-btn">
            <a>
              Know More{' '}
              <i class="i i-external-link" style={{ marginLeft: '5px' }} />
            </a>
          </span>
        </div>

        <div class="panel-body payment-capture-panel">
          {this.state.lateConfigExist && (
            <div class="payment-capture-panel-row">
              <div class="panel-content">
                <div
                  class={`left-panel ${
                    this.state.lateConfigExist === 'automatic'
                      ? 'is-active'
                      : ''
                  }`}
                  style={{ marginRight: '5px' }}
                >
                  <div class="panel-header">
                    <h4>
                      <b>Automatic Capture</b>
                    </h4>
                    <input
                      type="radio"
                      checked={this.state.lateConfigExist === 'automatic'}
                      onClick={() => {
                        this.handleCaptureInitiationDone('automatic');
                      }}
                    />
                  </div>
                  <div class="panel-description">
                    <p>Payments will be captured automatically</p>
                  </div>
                </div>
                <div
                  class={`right-panel ${
                    this.state.lateConfigExist === 'manual' ? 'is-active' : ''
                  }`}
                  style={{ marginLeft: '5px' }}
                >
                  <div class="panel-header">
                    <h4>
                      <b>Manual Capture</b>
                    </h4>
                    <input
                      type="radio"
                      checked={this.state.lateConfigExist === 'manual'}
                      onClick={() => {
                        this.handleCaptureInitiationDone('manual');
                      }}
                    />
                  </div>
                  <div class="panel-description">
                    <p>Capture the payments manually</p>
                  </div>
                </div>
              </div>
              <div class="description">
                All payments authorised within 5 days of creation will be
                captured automatically.
              </div>
              <HorizontalTimeline timelineItems={this.state.timelineItems} />
            </div>
          )}
          {this.state.lateConfigExist === null && (
            <>
              <div class="description">
                Payments must be captured once they get authorised or else
                payments will be auto refunded to customers. Set default capture
                and auto refund settings to control your payments better.
              </div>
              <button
                class="btn btn-primary"
                onClick={this.configureNow}
                style={{ marginTop: '15px' }}
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
