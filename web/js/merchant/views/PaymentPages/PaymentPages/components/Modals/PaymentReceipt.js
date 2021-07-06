import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import {
  trackClickOnSavePaymentReceipts,
} from '../../ga';

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

  trackReceipt = (event, options) => {
    this.props.tracking.trackEvent(
      window.rzpQ.paymentPages().success(
        `pp.receipt.${event}`,
        {
          template: '',
          page_id: this.PAGE_ID,
        },
        options,
      ),
    );
  };

  componentDidMount() {
    this.trackReceipt('open_settings');
  }

  componentWillUnmount() {
    this.trackReceipt('close_settings');
  }

  open80gDetailsModal = () => {
    this.props.openModal({
      size: 'medium',
      component: (
        <Merchant80gDetails trackFn={this.trackReceipt} get80gDetails={this.get80gDetails} />
      ),
    });
  };

  get80gDetails = (data) => {
    this.setState({
      '80_details': data,
    });
  };

  onSubmit = (formData) => {
    analyticsTrack({
      objectName: 'receipts',
      actionName: 'saved',
      screen: 'create payment page',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    //preparing track data for GA
    const trackData = [];

    if(formData.enable_custom_serial_number == 0) {
      trackData.push('Automated');
    } else {
      trackData.push('Manual');
    }

    if(formData.enable_80g_details) {
      trackData.push('80G');
    }
    trackClickOnSavePaymentReceipts(trackData);

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

    this.trackReceipt('save', {
      '80_details': this.state['80_details'],
      input_field: data.selected_udf_field,
    });
  };

  trackSendingOptions = (event) => {
    analyticsTrack({
      objectName: `receipts ${event.target.value === '0' ? 'automated' : 'manual'}`,
      actionName: 'clicked',
      screen: 'create payment page',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    this.trackReceipt(event.target.value === '0' ? 'automated' : 'manual');
  };

  trackInputField = () => {
    this.trackReceipt('select_input');
  };

  handleClose = () => {
    this.trackReceipt('cancel', {
      '80_details': this.state['80_details'],
      input_field:
        this.state.isInputFieldChecked && this.state.selectedInputField
          ? this.state.selectedInputField.name
          : '',
    });

    this.props.handleClose();
    this.trackReceipt('save', {
      '80_details': this.state['80_details'],
      input_field: data.selected_udf_field,
    });
  };

  trackSendingOptions = (event) => {
    this.trackReceipt(event.target.value === '0' ? 'automated' : 'manual');
  };

  trackInputField = () => {
    this.trackReceipt('select_input');
  };

  handleClose = () => {
    this.trackReceipt('cancel', {
      '80_details': this.state['80_details'],
      input_field:
        this.state.isInputFieldChecked && this.state.selectedInputField
          ? this.state.selectedInputField.name
          : '',
    });

    this.props.handleClose();
  };

  render() {
    const props = this.props;

    return (
      <ModalMask maskClosable={false} class="PaymentpagesReceipt">
        <Modal class="PaymentpagesReceipt" onClose={this.handleClose}>
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
                    analyticsTrack({
                      objectName: 'receipts input field',
                      actionName: 'chosen',
                      screen: 'create payment page',
                      properties: {
                        ...getCommonAnalyticsProperties(window.rzp_user),
                      },
                    });
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
                  onBlur={this.trackInputField}
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
                    analyticsTrack({
                      objectName: 'receipts 80-G',
                      actionName: 'clicked',
                      screen: 'create payment page',
                      properties: {
                        ...getCommonAnalyticsProperties(window.rzp_user),
                      },
                    });
                    this.setState({
                      is80GDetailsChecked: e.target.checked,
                    });
                  }}
                  onBlur={(e) => {
                    this.trackReceipt(e.target.checked ? '80g_on' : '80g_off');
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
                <Button.Transparent type="button" onClick={this.handleClose}>
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
