import { connect } from 'react-redux';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import ModalHeader from 'common/ui/ModalHeader';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import { AsyncBtn } from 'common/new-ui/Button';

import { fetchConfigForVirtualAccount } from 'merchant/reducers/virtualaccounts';

import Spinner from 'common/ui/Spinner';

import {
  isBlank,
  rupeesToPaise,
  paiseToRupees,
  titleCase,
  classList,
} from 'common/utils/rzp-utils';

import {
  validateAlphanumericWithMaxLength,
  validateAlphanumericWithStrictLength,
} from 'common/utils/validators';
import { closeModal } from 'merchant_common/reducers/modals';

import {
  DESCRIPTOR_LENGTH_BANK_ACCOUNT,
  DESCRIPTOR_LENGTH_VPA,
  getStyle_DescriptorInput_BankAccount,
  getStyle_AddOnBefore_BankAccount,
  getStyle_DescriptorInput_VPA,
  getStyle_AddOnBefore_VPA,
  getStyle_AddOnAfter_VPA,
} from 'merchant/views/SmartCollect/VirtualAccounts/helpers';

@connect((state) => ({ va_config: state.virtualaccounts.va_config }), {
  closeModal,
  ...NotificationsActions,
  fetchConfigForVirtualAccount,
})
export default class EnableTransferMode extends React.Component {
  componentDidMount() {
    // Make call only when va_config is not available in store, or call failed last time when CreateVirtualAccount modal was opened
    if (!this.props.va_config || !Object.keys(this.props.va_config).length) {
      this.props.fetchConfigForVirtualAccount();
    }
  }

  onSubmit = (data) => {
    const { isForBankAccount, isForUPIAddress } = this.props;
    const { descriptor } = data;

    const payload = {
      types: [],
    };

    if (isForUPIAddress) {
      payload.types.push('vpa');

      if (descriptor) {
        payload['vpa'] = {
          descriptor: descriptor,
        };
      }
    } else if (isForBankAccount) {
      payload.types.push('bank_account');

      if (descriptor) {
        payload['bank_account'] = {
          descriptor: descriptor,
        };
      }
    }

    return this.props.updateVirtualAccountDetails(payload);
  };

  render() {
    const { isForBankAccount, isForUPIAddress, va_config } = this.props;

    if (!va_config) {
      return (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      );
    }

    let formTitle, buttonLabel, field;
    let toAutoCreateDescriptor;
    let descriptorLimit_BankAccount, descriptorLimit_VPA;

    if (isForUPIAddress) {
      if (
        Object.keys(va_config).length &&
        va_config.hasOwnProperty('vpa') &&
        va_config.vpa.isDescriptorEnabled &&
        va_config.vpa.prefix
      ) {
        let vpaHandle = va_config.vpa.prefix && va_config.vpa.prefix.split('.')[1];

        descriptorLimit_VPA = DESCRIPTOR_LENGTH_VPA - vpaHandle.length;
      } else {
        toAutoCreateDescriptor = true;
      }

      formTitle = 'Enable UPI Transfer';

      if (toAutoCreateDescriptor) {
        buttonLabel = 'Yes, Enable';

        field = <div>UPI ID will be auto generated. Are you sure you want to proceed?</div>;
      } else {
        buttonLabel = 'Enable UPI Transfer';

        field = (
          <Input
            class="Input--vTop no-margin  "
            label="Virtual UPI ID"
            name="descriptor"
            description="If left blank, a UPI ID will be auto generated"
            validator={(val) => {
              if (!validateAlphanumericWithStrictLength(val, descriptorLimit_VPA)) {
                return `Enter only Alphanumeric, ${descriptorLimit_VPA} characters`;
              }
            }}
            style={getStyle_DescriptorInput_VPA(va_config)}
            addonBefore={
              <span style={getStyle_AddOnBefore_VPA(va_config)}>{va_config.vpa.prefix}</span>
            }
            addonAfter={
              <span style={getStyle_AddOnAfter_VPA(va_config)}>@{va_config.vpa.handle}</span>
            }
          />
        );
      }
    } else if (isForBankAccount) {
      if (
        Object.keys(va_config).length &&
        va_config.hasOwnProperty('bank_account') &&
        va_config.bank_account.isDescriptorEnabled
      ) {
        let bankAccountHandle = va_config.bank_account.prefix;
        descriptorLimit_BankAccount = DESCRIPTOR_LENGTH_BANK_ACCOUNT - bankAccountHandle.length;
      } else {
        toAutoCreateDescriptor = true;
      }

      formTitle = 'Enable Account Transfer';

      if (toAutoCreateDescriptor) {
        buttonLabel = 'Yes, Enable';

        field = (
          <div>An account number will be auto generated. Are you sure you want to proceed?</div>
        );
      } else {
        buttonLabel = 'Enable Account Transfer';

        field = (
          <Input
            class="Input--vTop no-margin"
            label="Account Number"
            name="descriptor"
            description="If left blank, an account number will be auto generated"
            validator={(val) => {
              if (!validateAlphanumericWithMaxLength(val, descriptorLimit_BankAccount)) {
                return `Enter only Alphanumeric, upto ${descriptorLimit_BankAccount} characters`;
              }
            }}
            style={getStyle_DescriptorInput_BankAccount(va_config)}
            onChange={(e) => {
              let val = e.target.value;

              if (validateAlphanumericWithMaxLength(val, descriptorLimit_BankAccount)) {
                e.target.value = val.toUpperCase();
              }
            }}
            addonBefore={
              <span style={getStyle_AddOnBefore_BankAccount(va_config)}>
                {va_config.bank_account.prefix}
              </span>
            }
          />
        );
      }
    }

    return (
      <div>
        <ModalHeader title={formTitle} onCloseClick={this.props.closeModal} />
        <div class="modal-body">
          <Form onSubmit={this.onSubmit} class="filters">
            {field}
            <br />
            <div class="Modal__actions">
              {toAutoCreateDescriptor && (
                <button type="button" className="btn btn-default" onClick={this.props.closeModal}>
                  No, Cancel
                </button>
              )}

              <button
                class={classList(
                  'btn btn-primary',
                  toAutoCreateDescriptor ? 'pull-right' : 'btn-block',
                )}
              >
                {buttonLabel}
              </button>
            </div>
          </Form>
        </div>
      </div>
    );
  }
}
