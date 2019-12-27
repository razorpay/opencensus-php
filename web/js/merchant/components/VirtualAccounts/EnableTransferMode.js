import { connect } from 'react-redux';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import ModalHeader from 'common/ui/ModalHeader';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import { AsyncBtn } from 'common/new-ui/Button';

import {
  isBlank,
  rupeesToPaise,
  paiseToRupees,
  titleCase,
  classList,
} from 'common/utils/rzp-utils';

import { validateVABankAccount } from 'common/utils/validators';
import { closeModal } from 'merchant_common/reducers/notifications';

@connect(state => ({ config: state.config.config }), {
  closeModal,
  ...NotificationsActions,
})
export default class EnableTransferMode extends React.Component {
  onSubmit = data => {
    const { isForBankAccount, isForUPIAddress } = this.props;
    const { descriptor } = data;

    const receivers = {
      types: [],
    };

    if (isForUPIAddress) {
      receivers.types.push('vpa');

      if (descriptor) {
        receivers['vpa'] = {
          descriptor: descriptor,
        };
      }
    } else if (isForBankAccount) {
      receivers.types.push('bank_account');

      if (descriptor) {
        receivers['bank_account'] = {
          descriptor: descriptor,
        };
      }
    }

    const payload = { receivers };

    return this.props.updateVirtualAccountDetails(payload);
  };

  render() {
    const { isForBankAccount, isForUPIAddress, config } = this.props;
    let formTitle, buttonLabel, field, descriptorLimit;

    const isAutoCreateBankAccount = isForBankAccount && !config.handle;

    if (isForBankAccount && config.handle) {
      if (config.handle.length === 3) {
        descriptorLimit = 10;
      } else if (config.handle.length === 4) {
        descriptorLimit = 9;
      }
    }

    if (isForUPIAddress) {
      buttonLabel = formTitle = 'Enable UPI Transfer';

      field = (
        <Input
          class="Input--vTop no-margin  "
          label="Virtual UPI ID"
          name="descriptor"
          description="If left blank, a UPI ID will be auto generated"
        />
      );
    } else if (isForBankAccount) {
      buttonLabel = formTitle = 'Enable Account Transfer';

      if (isAutoCreateBankAccount) {
        buttonLabel = 'Yes, Enable';

        field = (
          <div>
            An account number will be auto generated. Are you sure you want to
            proceed?
          </div>
        );
      } else {
        field = (
          <Input
            class="Input--vTop no-margin"
            label="Account Number"
            name="descriptor"
            placeholder={`Alphanumberic, upto ${descriptorLimit} characters`}
            description="If left blank, an account number will be auto generated"
            validator={val => {
              if (!validateVABankAccount(val, descriptorLimit)) {
                return `Enter only Alphanumberic, upto ${descriptorLimit} characters`;
              }
            }}
            onChange={e => {
              let val = e.target.value;

              if (validateVABankAccount(val, descriptorLimit)) {
                e.target.value = val.toUpperCase();
              }
            }}
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
              {isAutoCreateBankAccount && (
                <button
                  type="button"
                  className="btn btn-default"
                  onClick={this.props.closeModal}
                >
                  No, Cancel
                </button>
              )}

              <button
                class={classList(
                  'btn btn-primary',
                  isAutoCreateBankAccount ? 'pull-right' : 'btn-block'
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
