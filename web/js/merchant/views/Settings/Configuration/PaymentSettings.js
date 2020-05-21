import { Component } from 'react';
import { connect } from 'react-redux';
import {
  updateFeatures,
  fetchLateAuthConfig,
  createLateAuthConfig,
} from 'merchant/reducers/config';
import { showNotification } from 'merchant_common/reducers/notifications';
import ShowWhen from 'merchant/components/ShowWhen';
import Popover, { PopoverTitle, PopoverBody } from 'common/ui/Popover';
import Input from 'common/new-ui/Input';
import * as ModalActions from 'merchant_common/reducers/modals';
import CaptureInitiation from 'merchant/views/Settings/Configuration/PaymentCaptureComponents/CaptureInitiation';

const CONSTANTS = {
  capture_mode_options: [
    { label: '', name: '' },
    { label: 'Auto Capture', name: 1 },
    { label: 'Manual Capture', name: 0 },
  ],

  timeout_action_options: [
    { label: '', name: '' },
    { label: 'Normal Refund', name: 'normal' },
    { label: 'Instant Refund', name: 'optimum' },
    {
      label: 'Manual Capture or Refund',
      name: 'default',
    },
  ],

  capture_setting_descriptions: [
    'Payments will be captured by Razorpay automatically',
    'Payments have to be captured manually by you via the API or the dashboard',
  ],
  automatic_capture_descriptions: [
    'All payments authorised within 5 days of creation will be captured automatically',
    'Setup capture timeout according to your business needs. Payments authorised within timeout will be captured and others will be refunded to your customers',
  ],
};

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
      doesConfigExist: false,
    };
  }

  componentDidMount() {
    this.props.fetchLateAuthConfig();
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

  handleCaptureMode = e => {
    this.setState({
      capture_mode: e.target.value,
    });
  };

  handleTimeoutAction = e => {
    this.setState({
      timeout_action: e.target.value,
    });
  };

  handleAuthorisationTimeout = e => {
    this.setState({
      authorisation_timeout: e.target.value,
    });
  };

  isSaveDisabled = () => {
    if (
      this.state.capture_mode.toString() &&
      this.state.timeout_action.toString() &&
      this.state.authorisation_timeout.toString()
    )
      return false;
    else return true;
  };

  handleSave = () => {
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

    this.props.createLateAuthConfig(payload, method).then(res => {
      this.props.showNotification({
        type: 'success',
        message: this.state.doesConfigExist
          ? 'Config updated successfully'
          : 'Config created successfully',
      });
    });
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
        <CaptureInitiation
          closeModal={this.props.closeModal}
          header="Capture Settings"
          handleDone={this.handleCaptureInitiationDone}
          sectionTitles={['Automatic Capture', 'Manual Capture']}
          sectionDescriptions={CONSTANTS.capture_setting_descriptions}
        />
      ),
    });
  };

  handleCaptureInitiationDone = () => {
    this.props.closeModal();
    this.props.openModal({
      size: 'small',
      component: (
        <CaptureInitiation
          closeModal={this.props.closeModal}
          header="Automatic Capture"
          sectionTitles={[
            'Capture all payments automatically',
            'Setup custom timeout',
          ]}
          sectionDescriptions={CONSTANTS.automatic_capture_descriptions}
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

        <div class="panel-body payment-settings">
          <div class="description">
            Payments must be captured once they get authorised or else payments
            will be auto refunded to customers. Set defualt capture and auto
            refund settings to control your payments better.
          </div>
          <button
            class="btn btn-primary"
            onClick={this.configureNow}
            style={{ marginTop: '15px' }}
          >
            Configure Now
          </button>
        </div>
      </div>
    );
  }
}

const InfoLabel = () => {
  return (
    <>
      Capture Timeout <i class="i i-info-circle" />
      <Popover align="top" theme="dark">
        <PopoverBody>
          Payments outside this timeout will be considered as late authorised.
        </PopoverBody>
      </Popover>
    </>
  );
};
