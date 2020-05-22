import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';
import { connect } from 'react-redux';
import Form from 'common/new-ui/Form';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Input, { Label, Description } from 'common/new-ui/Input';
import { PowerSelect } from 'react-power-select';
import { openModal } from 'merchant_common/reducers/modals';
import { classList } from 'common/utils/rzp-utils';

import Merchant80gDetails from './Merchant80gDetails';

@connect(null, { openModal })
export default class PaymentReceipt extends React.Component {
  constructor(props) {
    super(props);

    const { paymentPageEntity, formItems } = props;
    const receipt = paymentPageEntity.receipt || {};

    let selectedInputField = null;

    this.options = formItems.map(item => {
      if (item.name === receipt.selected_udf_field) {
        selectedInputField = item;
      }

      return {
        name: item.name,
        title: item.title,
        disabled:
          [
            paymentPageEntity.settings.checkout_options.email,
            paymentPageEntity.settings.checkout_options.phone,
          ].indexOf(item.name) > -1,
      };
    });

    this.state = {
      isInputFieldChecked: !!receipt.selected_udf_field,
      is80GDetailsChecked: receipt.enable_80g_details === '1',
      selectedInputField,
    };
  }

  open80gDetailsModal = () => {
    this.props.openModal({
      size: 'medium',
      component: <Merchant80gDetails />,
    });
  };

  onSubmit = formData => {
    const data = {
      enable_receipt: 1, // Currently, enabling in all cases. Later, can add toggle
      enable_custom_serial_number: formData.enable_custom_serial_number,
      selected_udf_field:
        this.state.isInputFieldChecked && this.state.selectedInputField
          ? this.state.selectedInputField.name
          : '',
      enable_80g_details: formData.enable_80g_details ? '1' : '0',
    };

    this.props.handleSave(data);
    this.props.handleClose();
  };

  render() {
    const props = this.props;

    return (
      <ModalMask maskClosable={false} class="PaymentpagesReceipt">
        <Modal class="PaymentpagesReceipt" onClose={props.handleClose}>
          <ModalContent>
            <div class="main-title">Payment Receipts Settings</div>

            <Form class="Receipts-form" onSubmit={this.onSubmit}>
              <div class="settings-section">
                <Input.Radio
                  name="enable_custom_serial_number"
                  defaultValue={
                    props.paymentPageEntity.receipt
                      ? props.paymentPageEntity.receipt
                          .enable_custom_serial_number
                      : ''
                  }
                  options={[
                    {
                      label: (
                        <div>
                          <Label text="Send Automated Receipts" />
                          <Description text="Receipt is sent immediately after a payment" />
                        </div>
                      ),
                    },
                    {
                      label: (
                        <div>
                          <Label text="Send Manual Receipts" />
                          <Description text="Custom reference ID can be added for each receipt" />
                        </div>
                      ),
                    },
                  ]}
                  class="Input--vTop Input--theme"
                />

                <div class="doc-links">
                  <a href="https://razorpay.com/payment-pages" target="_blank">
                    View Sample Receipt <i class="i i-external-link" />
                  </a>
                  <a href="https://razorpay.com/payment-pages" target="_blank">
                    Learn More <i class="i i-external-link" />
                  </a>
                </div>
              </div>

              <div class="settings-section">
                <Input.Check
                  fieldLabel={() => (
                    <div>
                      <b class="m-r">Show an Input Field on Receipt</b>{' '}
                      (Optional)
                    </div>
                  )}
                  onChange={e => {
                    this.setState({
                      isInputFieldChecked: e.target.checked,
                    });
                  }}
                  checked={this.state.isInputFieldChecked}
                  defaultChecked={this.state.isInputFieldChecked}
                />

                <PowerSelect
                  class="PowerSelect-PickField ps-in-modal"
                  placeholder="Pick an input field from this page"
                  options={this.options}
                  selected={
                    this.state.selectedInputField
                      ? this.state.selectedInputField.title
                      : null
                  }
                  optionComponent={({ option }) => {
                    return (
                      <div>
                        {option.title}
                        {option.disabled && <span>(show by default)</span>}
                      </div>
                    );
                  }}
                  onChange={({ option = null }) => {
                    this.setState({
                      selectedInputField: option,
                    });
                  }}
                  showClear={false}
                  searchEnabled={false}
                  disabled={!this.state.isInputFieldChecked}
                />

                <Description
                  text="Customer's input will be shown on the receipt"
                  class={classList(
                    !this.state.isInputFieldChecked && 'Input-desc--disabled'
                  )}
                />
              </div>

              <div class="settings-section">
                <Input.Check
                  name="enable_80g_details"
                  fieldLabel={() => (
                    <div>
                      <b class="m-r">Show 80G Details</b> (Optional)
                    </div>
                  )}
                  onChange={e => {
                    this.setState({
                      is80GDetailsChecked: e.target.checked,
                    });
                  }}
                  checked={this.state.is80GDetailsChecked}
                  defaultChecked={this.state.is80GDetailsChecked}
                  description={() => (
                    <div
                      class={classList(
                        !this.state.is80GDetailsChecked &&
                          'Input-desc--disabled'
                      )}
                    >
                      To manage your 80-G details,{' '}
                      <Button
                        type="button"
                        class="Button--transparent"
                        onClick={this.open80gDetailsModal}
                      >
                        Click here
                      </Button>
                    </div>
                  )}
                />
              </div>

              <footer>
                <Button.Transparent type="button" onClick={props.handleClose}>
                  Cancel
                </Button.Transparent>
                <Button.Primary type="submit">Save</Button.Primary>
              </footer>
            </Form>
          </ModalContent>
        </Modal>
      </ModalMask>
    );
  }
}
