import React from 'react';
import BulbImage from 'assets/lighten-bulb.svg';
import { connect } from 'react-redux';

import Button from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import debounce from 'common/utils/debounce';
import { validateAlphanumeric } from 'common/utils/validators';
import { validateVPACustomPrefix, saveVPACustomPrefix } from 'merchant/reducers/virtualaccounts';
import {
  MERCHANT_PREFIX_MIN_LENGTH_VPA,
  MERCHANT_PREFIX_MAX_LENGTH_VPA,
  get_VPA_Handle,
  getStyle_CustomPrefixInput_VPA,
  getStyle_AddOnBefore_VPA_Prefix,
  getStyle_AddOnAfter_VPA_Prefix,
} from 'merchant/views/SmartCollect/VirtualAccounts/helpers';
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

class VPAPrefixModal extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      merchantPrefix: '',
      status: {},
      isValidating: false,
      isSaving: false,
    };

    this.debounce_validateMerchantPrefix = debounce(this.validateMerchantPrefix.bind(this), 50);
  }

  get isInvalidMerchantPrefix() {
    return validateCustomMerchantPrefix()(this.state.merchantPrefix);
  }

  validateMerchantPrefix = (value) => {
    this.setState({
      status: {},
      isValidating: true,
    });

    validateVPACustomPrefix(value)
      .then((resp) => {
        const newState = {
          isValidating: false,
        };

        if (!this.isInvalidMerchantPrefix) {
          // We need to validate MerchantPrefix due to setState is async
          const isValid = resp.data.is_valid;

          newState.status = {
            type: `text-${isValid ? 'success' : 'danger'}`,
            message: isValid ? 'Prefix available' : 'Prefix unavailable',
          };
        }

        this.setState(newState);
      })
      .catch((error) => {
        this.props.showNotification({
          type: 'error',
          message: error.errors[0],
        });

        this.setState({
          isValidating: false,
        });
      });
  };

  handleVPAPrefix = (event) => {
    const { value } = event.target;

    const newState = {
      merchantPrefix: value.toLowerCase(),
    };

    const isInvalidValue = validateCustomMerchantPrefix()(value);

    if (this.state.status.type && isInvalidValue) {
      newState.status = {};
    }

    this.setState(newState, () => {
      setTimeout(() => {
        if (!isInvalidValue) {
          this.debounce_validateMerchantPrefix(value);
        }
      }, 5);
    });
  };

  saveVPACustomPrefix = () => {
    this.props.track('prefix.save');

    this.setState({
      isSaving: true,
    });

    return this.props
      .saveVPACustomPrefix(this.state.merchantPrefix)
      .then(() => {
        this.setState({
          isSaving: false,
        });

        this.props.showNotification({
          type: 'success',
          message: 'Custom VPA prefix is updated',
        });

        this.props.closeModal();
      })
      .catch((error) => {
        this.props.showNotification({
          type: 'error',
          message: error.errors[0],
        });

        this.setState({
          isSaving: false,
        });
      });
  };

  render() {
    const { rzp_prefix, merchant_prefix } = this.props;
    const { status, isValidating, merchantPrefix, isSaving } = this.state;

    const handle = get_VPA_Handle(this.props.handle);

    const disabled =
      status.type !== 'text-success' || isValidating || this.isInvalidMerchantPrefix || isSaving;

    return (
      <div className="PopOver--Modal">
        <Input
          autoRender
          name="descriptorVPA"
          size="vpa_custom"
          label="Prefix Value"
          placeholder={merchant_prefix}
          style={getStyle_CustomPrefixInput_VPA(rzp_prefix, handle)}
          validator={validateCustomMerchantPrefix()}
          onChange={this.handleVPAPrefix}
          value={merchantPrefix}
          description={
            <>
              <div className="remaining-count">
                {merchantPrefix.length} / {MERCHANT_PREFIX_MAX_LENGTH_VPA}
              </div>

              <div className={`status ${status.type}`}>{status.message}</div>
            </>
          }
          addonBefore={
            <span style={getStyle_AddOnBefore_VPA_Prefix(rzp_prefix)}>{rzp_prefix}.</span>
          }
          addonAfter={<span style={getStyle_AddOnAfter_VPA_Prefix(handle)}>{handle}</span>}
          onBlur={(event) => {
            this.props.track('prefix.enter');

            const message = validateCustomMerchantPrefix()(event.target.value);
            if (message) {
              this.props.track('smartcollect.va.prefix.error', {
                message,
              });
            }
          }}
        />
        <div className="upi-example">
          UPI ID Example{' '}
          <span>
            {rzp_prefix}.{merchantPrefix || merchant_prefix}
            {handle}
          </span>
        </div>
        <img src={BulbImage} /> Protip: Highlight your brand by adding it as a prefix to UPI IDs.
        <div className="Modal-actions">
          <Button.Transparent
            className="Cancel-btn"
            type="button"
            onClick={() => {
              this.props.closeModal();

              this.props.track('prefix.cancel');
            }}
          >
            <span>&times;</span>
            Cancel
          </Button.Transparent>

          <Button.Transparent
            className="Save-btn"
            type="button"
            disabled={disabled}
            onClick={this.saveVPACustomPrefix}
          >
            <span className="icon i-check" />
            Save
          </Button.Transparent>
        </div>
      </div>
    );
  }
}

function validateCustomMerchantPrefix() {
  const minLength = MERCHANT_PREFIX_MIN_LENGTH_VPA;
  const maxLength = MERCHANT_PREFIX_MAX_LENGTH_VPA;

  return (value) => {
    const isValidMinLength = value.length >= minLength;
    if (!isValidMinLength) {
      return `Must be greater than ${minLength} characters.`;
    }

    const isValidMaxLength = value.length <= maxLength;
    if (!isValidMaxLength) {
      return `Must be less than ${maxLength} characters.`;
    }

    const isValidAlphanumeric = validateAlphanumeric(value);
    if (!isValidAlphanumeric) {
      return 'Special characters not allowed';
    }

    return '';
  };
}

export default connect((state) => state.virtualaccounts.va_config.vpa, {
  closeModal,
  showNotification,
  saveVPACustomPrefix,
})(VPAPrefixModal);
