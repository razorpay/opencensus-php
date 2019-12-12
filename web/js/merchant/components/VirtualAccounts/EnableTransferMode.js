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
} from 'common/utils/rzp-utils';
import { closeModal } from 'merchant_common/reducers/notifications';

@connect(state => ({}), {
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
    const { isForBankAccount, isForUPIAddress } = this.props;
    let formTitle, buttonLabel, field;

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

      field = (
        <Input
          class="Input--vTop no-margin"
          label="Account Number"
          name="descriptor"
          description="If left blank, an account number will be auto generated"
        />
      );
    }

    return (
      <div>
        <ModalHeader title={formTitle} onCloseClick={this.props.closeModal} />
        <div class="modal-body">
          <Form onSubmit={this.onSubmit} class="filters">
            {field}
            <br />
            <div class="Modal__actions">
              <button class="btn btn-primary btn-block">{buttonLabel}</button>
            </div>
          </Form>
        </div>
      </div>
    );
  }
}
