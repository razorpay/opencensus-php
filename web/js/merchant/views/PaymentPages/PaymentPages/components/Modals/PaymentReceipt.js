import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import track from '../../Wysiwyg/track';

import Form from 'common/new-ui/Form';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Input, { Label, Description } from 'common/new-ui/Input';
import { PowerSelect } from 'react-power-select';
import { openModal } from 'merchant_common/reducers/modals';
import { classList } from 'common/utils/rzp-utils';
import { DocLink } from 'merchant/components/DocsLink'

import Merchant80gDetails from './Merchant80gDetails';

@connect(null, { openModal })
@RTracking(() => window.rzpQ.component('PaymentReceipt'))
export default class PaymentReceipt extends React.Component {
  constructor(props) {
    super(props);

    const { paymentPageEntity, formItems } = props;
    const receipt = paymentPageEntity.receipt || {};

    let selectedInputField = null;

    const udfFormFields = formItems.filter((field) => {
      const isUDFField = !field.hasOwnProperty('item');

      return isUDFField;
    });

    this.options = udfFormFields.map((field) => {
      if (field.name === receipt.selected_udf_field) {
        selectedInputField = field;
      }

      return {
        name: field.name,
        title: field.title,
        disabled:
          [
            paymentPageEntity.settings.checkout_options.email,
            paymentPageEntity.settings.checkout_options.phone,
          ].indexOf(field.name) > -1,
      };
    });

    this.PAGE_ID = props.paymentPageEntity.id;
    this.TEMPLATE = props.paymentPageEntity.description;
    this.UUID = `payment_receipt_${Date.now()}`;

    this.state = {
      isInputFieldChecked: !!receipt.selected_udf_field,
      is80GDetailsChecked: receipt.enable_80g_details === '1',
      selectedInputField,
      '80_details': '',
    };
  }

  componentDidMount() {
    track.receipt.open();
  }

  componentWillUnmount() {
    track.receipt.close();
  }

  open80gDetailsModal = () => {
    this.props.openModal({
      size: 'medium',
      component: (
        <Merchant80gDetails get80gDetails={this.get80gDetails} />
      ),
    });
  };

  get80gDetails = (data) => {
    this.setState({
      '80_details': data,
    });
  };

  onSubmit = (formData) => {

    const data = {
      enable_receipt: 1, // Currently, enabling in all cases. Later, can add toggle
      enable_custom_serial_number: formData.enable_custom_serial_number,
      selected_udf_field:
        this.state.isInputFieldChecked && this.state.selectedInputField
          ? this.state.selectedInputField.name
          : '',
      enable_80g_details: formData.enable_80g_details ? '1' : '0',
    };

    const promise = this.props.handleSave(data);

    if (promise && promise.then) {
      promise.then((resp) => {
        this.props.handleClose();
      });
    } else {
      this.props.handleClose();
    }

    track.receipt.save(
      formData.enable_custom_serial_number == 0,
      data.selected_udf_field,
      formData.enable_80g_details,
    );
  };

  trackSendingOptions = (event) => {
    track.receipt.clickSendingOption(event.target.value === '0' ? 'automated' : 'manual');
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
                      ? props.paymentPageEntity.receipt.enable_custom_serial_number
                      : ''
                  }
                  onClick={this.trackSendingOptions}
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
                  <DocLink
                    href="https://razorpay.com/docs/payment-pages/receipt-80g/#pdf-receipt-to-customers"
                    target="_blank"
                  >
                    View Sample Receipt <i class="i i-external-link" />
                  </DocLink>
                  <DocLink href="https://razorpay.com/docs/payment-pages/receipt-80g" target="_blank">
                    Learn More <i class="i i-external-link" />
                  </DocLink>
                </div>
              </div>

              <div class="settings-section">
                <Input.Check
                  fieldLabel={() => (
                    <div>
                      <b class="m-r">Show an Input Field on Receipt</b> (Optional)
                    </div>
                  )}
                  onChange={(e) => {
                    track.receipt.checkInputFields();
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
                    this.state.selectedInputField ? this.state.selectedInputField.title : null
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
                  class={classList(!this.state.isInputFieldChecked && 'Input-desc--disabled')}
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
                  onChange={(e) => {
                    track.receipt.check80GDetails(e.target.checked ? '80g_on' : '80g_off');
                    this.setState({
                      is80GDetailsChecked: e.target.checked,
                    });
                  }}
                  checked={this.state.is80GDetailsChecked}
                  defaultChecked={this.state.is80GDetailsChecked}
                  description={() => (
                    <div
                      class={classList(!this.state.is80GDetailsChecked && 'Input-desc--disabled')}
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
                <Button.Primary type="submit">{props.saveBtnLabel || 'Save'}</Button.Primary>
              </footer>
            </Form>
          </ModalContent>
        </Modal>
      </ModalMask>
    );
  }
}
