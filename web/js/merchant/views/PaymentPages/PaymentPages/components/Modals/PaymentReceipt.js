import React from 'react';
import { ModalMask, Modal, ModalContent } from 'common/new-ui/Modal';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import track from '../../Wysiwyg/track';
import trackPB from '../../../../PaymentButton/PaymentButton/Details/track';

import Form from 'common/new-ui/Form';
import Button from 'common/new-ui/Button';
import Input, { Label, Description } from 'common/new-ui/Input';
import { PowerSelect } from 'react-power-select';
import { openModal } from 'merchant_common/reducers/modals';
import { DocLink } from 'merchant/components/DocsLink';
import { showNotification } from 'merchant_common/reducers/notifications';

import Merchant80gDetails from './Merchant80gDetails';
import { get80gMerchantDetails, set80gMerchantDetails } from 'merchant/reducers/profile';
import Spinner from 'common/ui/Spinner';
import Popover, { PopoverBody } from 'common/ui/Popover';

@connect(null, { openModal, showNotification })
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
      isLoading: true,
    };
  }

  componentDidMount() {
    track.receipt.open();
    // Fetch 80G details of merchant
    get80gMerchantDetails()
      .then((res) => {
        if (res && res.data) {
          // disable 80g checkbox if text_80g_12a is empty string
          this.setState((prevState) => ({
            isLoading: false,
            '80_details': {
              text_80g_12a: res.data.text_80g_12a,
              image_url_80g: res.data.image_url_80g,
            },
            is80GDetailsChecked: prevState.is80GDetailsChecked && res.data.text_80g_12a,
          }));
        } else {
          throw new Error();
        }
      })
      .catch(({ errors }) => {
        let err = errors;

        if (Array.isArray(err)) {
          err = [];

          if (errors.length) {
            errors.forEach((e) => {
              if (e && e.toLowerCase().indexOf('status code') === -1) {
                err.push(e);
              }
            });
          }

          err = err.length ? err : null;
        }

        if (!err) {
          err = `Some network error has occured`;
        }

        this.props.showNotification({
          type: 'error',
          message: err,
        });

        this.setState({
          isLoading: false,
        });
      });
  }

  componentWillUnmount() {
    track.receipt.close();
  }

  open80gDetailsModal = () => {
    this.props.openModal({
      size: 'medium',
      component: (
        <Merchant80gDetails set80gDetails={this.set80gDetails} data={this.state['80_details']} />
      ),
      className: 'Modal--80g',
    });
  };

  set80gDetails = (data) => {
    this.setState({
      '80_details': data,
      is80GDetailsChecked: !!data.text_80g_12a.length,
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
      promise.then(() => {
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
    const { trackingDetails } = this.props;

    if (trackingDetails) {
      if (trackingDetails.isPaymentPage) {
        track.receipt.clickSendingOption(event.target.value === '0' ? 'automated' : 'manual');
      } else {
        trackPB.receiptsType(
          trackingDetails.via,
          event.target.value === '0' ? 'automated' : 'manual',
        );
      }
    }
  };

  handleInputFieldChecked = (e) => {
    const { trackingDetails } = this.props;

    if (trackingDetails) {
      if (trackingDetails.isPaymentPage) {
        track.receipt.checkInputFields();
      } else {
        trackPB.inputFieldCheckbox(trackingDetails.via, e.target.checked);
      }
    }

    this.setState({
      isInputFieldChecked: e.target.checked,
    });
  };

  handle80GDetails = (e) => {
    const { trackingDetails } = this.props;

    if (trackingDetails) {
      if (trackingDetails.isPaymentPage) {
        track.receipt.check80GDetails(e.target.checked ? '80g_on' : '80g_off');
      } else {
        trackPB.details80gCheckbox(trackingDetails.via, e.target.checked);
      }
    }

    this.setState({
      is80GDetailsChecked: e.target.checked,
    });
  };

  handleInputField = ({ option = null }) => {
    this.setState({
      selectedInputField: option,
    });
  };

  remove80gDetails = () => {
    const reqPayload = {
      text_80g_12a: '',
      image_url_80g: '',
    };

    set80gMerchantDetails(reqPayload)
      .then((res) => {
        // uncheck 80g details checkbox if removing was successful
        this.setState({
          '80_details': {
            text_80g_12a: reqPayload.text_80g_12a,
            image_url_80g: reqPayload.image_url_80g,
          },
          is80GDetailsChecked: false,
        });

        if (res && res.success) {
          this.props.showNotification({
            type: 'success',
            message: '80G details are removed ',
            closeTimeout: 2500,
          });
        }
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors[0],
        });
      });
  };

  render() {
    const props = this.props;
    const { text_80g_12a, image_url_80g } = this.state['80_details'];
    return (
      <ModalMask maskClosable={false} class="PaymentpagesReceipt">
        <Modal class="PaymentpagesReceipt" onClose={props.handleClose} showCloseBtn={false}>
          <ModalContent>
            <div class="main-title">
              <i className="i i-receipt mr-8" />
              Payment Receipts Settings
            </div>

            {this.state.isLoading ? (
              <div class="page-spinner-container">
                <Spinner />
              </div>
            ) : (
              <Form class="Receipts-form" onSubmit={this.onSubmit}>
                <main>
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
                              <Label text="Send Receipts Automatically" />
                              <Description text="Receipts are emailed to customers immediately after payment." />
                            </div>
                          ),
                        },
                        {
                          label: (
                            <div>
                              <Label text="Don’t Send Receipts Automatically" />
                              <Description text="You may send receipts later from dashboard. Your own reference ID may be added too." />
                            </div>
                          ),
                        },
                      ]}
                      class="Input--vTop Input--theme"
                    />

                    <div class="doc-links">
                      <DocLink
                        href="https://razorpay.com/docs/payment-pages/receipt/#pdf-receipt-to-customers"
                        target="_blank"
                      >
                        Sample Receipt <i class="i i-external-link" />
                      </DocLink>
                      <DocLink
                        href="https://razorpay.com/docs/payment-pages/receipt/"
                        target="_blank"
                      >
                        Learn More <i class="i i-external-link" />
                      </DocLink>
                    </div>
                  </div>

                  <div class="settings-section">
                    <Input.Check
                      fieldLabel={() => (
                        <div>
                          <b class="m-r">Show Customer’s Information on Receipt</b>
                        </div>
                      )}
                      onChange={this.handleInputFieldChecked}
                      checked={this.state.isInputFieldChecked}
                      defaultChecked={this.state.isInputFieldChecked}
                    />

                    {this.state.isInputFieldChecked && (
                      <PowerSelect
                        class="PowerSelect-PickField ps-in-modal"
                        placeholder="Choose type of information"
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
                        onChange={this.handleInputField}
                        onBlur={this.trackInputField}
                        showClear={false}
                        searchEnabled={false}
                        disabled={!this.state.isInputFieldChecked}
                      />
                    )}
                  </div>

                  <div class="settings-section">
                    <div className="checkbox-container-80g">
                      <Input.Check
                        name="enable_80g_details"
                        fieldLabel={() => (
                          <div>
                            <b class="m-r">Show 80G Details on Receipt</b>
                          </div>
                        )}
                        autoRender
                        onChange={this.handle80GDetails}
                        checked={this.state.is80GDetailsChecked}
                        disabled={!text_80g_12a}
                        defaultChecked={this.state.is80GDetailsChecked}
                      />
                      <span className="rzp-tooltip-80g">
                        <i className="i i-info-outline" />
                        <Popover align="top" theme="dark" parentQuerySelector=".Modal-body">
                          <PopoverBody>
                            <div className="rzp-tooltip-title">For Donations</div>
                            80G-registered organisations can add their details on receipts to help
                            donors avail tax benefits
                          </PopoverBody>
                        </Popover>
                      </span>
                    </div>
                    <div>
                      {!text_80g_12a ? (
                        <Button
                          type="button"
                          class="Button--transparent Button--add-80g"
                          onClick={this.open80gDetailsModal}
                        >
                          + Add your 80G details
                        </Button>
                      ) : (
                        <div className="preview_80g">
                          <div className="preview_80g--text">{text_80g_12a}</div>
                          <div className="preview_80g--container">
                            {image_url_80g ? <img src={image_url_80g} alt="signature" /> : <div />}
                            <div className="preview_80g--container-right">
                              <Button
                                type="button"
                                class="Button--transparent"
                                onClick={this.open80gDetailsModal}
                              >
                                <i className="i i-edit-outline" />
                                Edit
                              </Button>
                              <div className="vertical-divider" />
                              <Button
                                type="button"
                                class="Button--transparent"
                                onClick={this.remove80gDetails}
                              >
                                <i className="i i-delete-outline" />
                                Remove
                              </Button>
                            </div>
                          </div>
                        </div>
                      )}
                    </div>
                  </div>
                </main>
                <footer>
                  <Button.Transparent type="button" onClick={props.handleClose}>
                    Cancel
                  </Button.Transparent>
                  <Button.Primary type="submit">{props.saveBtnLabel || 'Save'}</Button.Primary>
                </footer>
              </Form>
            )}
          </ModalContent>
        </Modal>
      </ModalMask>
    );
  }
}
