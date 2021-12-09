import React from 'react';
import { connect } from 'react-redux';
import moment from 'moment';

import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import { isMobileDevice } from 'merchant/components/Home/data';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import { onChangeNotes } from 'common/new-ui/Input/PairList';
import CustomerSelector from 'merchant/components/CustomerSelector';
import { dateCalculator } from 'common/new-ui/Input/Calendar';
import { timeCalculator } from 'common/new-ui/Input/Time';

import { validateAmount } from 'common/utils/validators';
import { rupeesToPaise, classList } from 'common/utils/rzp-utils';
import track from './track';

const FORM_CLASS_NAME = 'QRCode--Create-Form';

const FIXED_AMOUNT_OPTIONS = [
  {
    value: '0',
    label: 'No',
  },
  {
    value: '1',
    label: 'Yes',
  },
];

const USAGE_OPTIONS = [
  {
    value: 'multiple_use',
    label: 'Multiple Payments',
  },
  {
    value: 'single_use',
    label: 'Single Payment',
  },
];

const QR_TYPES = [
  {
    value: 'upi_qr',
    label: 'UPI QR',
  },
  {
    value: 'bharat_qr',
    label: 'Bharat QR',
  },
];

@connect((state) => ({
  user: state.session.user,
}))
export default class CreationForm extends React.Component {
  state = {
    isSubmitting: false,
    isSubmitDisabled: false,
    formData: {
      type: QR_TYPES[0].value,
      usage: USAGE_OPTIONS[0].value,
      fixed_amount: FIXED_AMOUNT_OPTIONS[0].value,
    },
  };

  closeByRef = React.createRef();

  componentDidMount() {
    this.toggleDisableState();

    track.open();
  }

  componentDidUpdate() {
    this.toggleDisableState();
  }

  onChangeNotes = (pairs) => {
    const notes = onChangeNotes(pairs);
    this.setState((prevState) => ({
      formData: {
        ...prevState.formData,
        notes,
      },
    }));

    track.field('notes', notes);
  };

  updateDate = (ts) => {
    const closeBy = ts ? moment(ts) : null;

    this.setState((prevState) => ({
      formData: {
        ...prevState.formData,
        close_by: closeBy,
      },
    }));

    track.field('close_by', closeBy);
  };

  handleSelectCustomer = (customer) => {
    this.setState({
      customer,
    });

    track.field('customer', customer);
  };

  // eslint-disable-next-line consistent-return
  onFieldChange = (event) => {
    const { formData } = this.state;
    const fieldValue = event.target.value;
    const fieldName = event.target.name;

    const isInvalidField = !fieldName || fieldName.indexOf('notes[') > -1;
    if (isInvalidField) return true;

    const updatedFormData = {
      ...formData,
      [fieldName]: fieldValue,
    };

    this.setState({
      formData: updatedFormData,
    });
  };

  // eslint-disable-next-line consistent-return
  onFieldBlur = (event) => {
    const fieldValue = event.target.value;
    const fieldName = event.target.name;

    const isInvalidField = !fieldName || fieldName.indexOf('notes[') > -1;
    if (isInvalidField) return true;

    track.field(fieldName, fieldValue);
  };

  toggleDisableState = () => {
    setTimeout(() => {
      // if value not selected, html marks it as ':invalid' which is tehnically valid in our case. Hence, relying on is-invalid.
      const invalidFields = document.querySelectorAll(`.${FORM_CLASS_NAME} .Input.is-invalid`);
      const isSubmitDisabled = invalidFields.length;

      if (this.state.isSubmitDisabled !== isSubmitDisabled) {
        this.setState({ isSubmitDisabled });
      }
    });
  };

  handleAdditionalOptions = () => {
    track.advancedOptions(!this.state.showAdditionalOptions);

    if (this.state.showAdditionalOptions) {
      const formTopEle = document.querySelector('.form-container input[name=usage]');

      if (formTopEle) {
        formTopEle.scrollIntoView({
          behavior: 'smooth',
          block: 'center',
        });
      }

      setTimeout(() => {
        this.setState({
          showAdditionalOptions: false,
        });
      }, 100);

      return;
    }

    this.setState(
      {
        showAdditionalOptions: true,
      },
      () => {
        setTimeout(() => {
          const scrollEle = document.querySelector('.closeBy');

          if (scrollEle) {
            scrollEle.scrollIntoView({
              behavior: 'smooth',
              block: 'center',
            });
          }

          // eslint-disable-next-line no-useless-return
          return;
        }, 100);
      },
    );
  };

  onSubmit = () => {
    const { formData, customer } = this.state;
    const payload = {
      ...formData,
    };

    if (formData.payment_amount) {
      payload.payment_amount = rupeesToPaise(formData.payment_amount);
    }

    if (formData.fixed_amount === '0') {
      delete payload.payment_amount;
    }

    if (customer && customer.id) {
      payload.customer_id = customer.id;
    }

    if (formData.close_by) {
      payload.close_by = payload.close_by.unix();
    }

    if (!this.state.noCloseBy) {
      delete payload.close_by;
    }

    payload.fixed_amount = parseInt(payload.fixed_amount, 10);

    this.setState({
      isSubmitting: true,
    });

    this.props
      .onSubmit(payload)
      .then((resp) => {
        this.setState({
          isSubmitting: false,
        });

        return resp;
      })
      .catch((err) => {
        this.setState({
          isSubmitting: false,
        });

        return err;
      });
  };

  onDateChange = (date) => {
    const curSelectedDateTime = this.state.formData.close_by;

    dateCalculator(date, curSelectedDateTime, this.updateDate);
  };

  onTimeChange = (date) => {
    const curDate = this.state.formData.close_by;

    timeCalculator(date, curDate, this.updateDate);
  };

  handleHasNoCloseBy = (e) => {
    if (e.target.checked) {
      setTimeout(() => {
        this.closeByRef.current.focus();
        this.closeByRef.current.click();
      }, 10);
    }

    this.setState({
      noCloseBy: e.target.checked,
    });
  };

  redirectToListView = () => {
    if (history) {
      this.props.history.push('/qr_codes');
    }
  };

  render() {
    const { props, state } = this;
    const isSubmitDisabled = state.isSubmitting || state.isSubmitDisabled;
    const { formData, showAdditionalOptions, isSubmitting } = state;

    return (
      <div class="QRCode--Create-wizard">
        <div class={props.isModalView ? 'title' : 'title mt-16'}>
          Create QR Code
          {isMobileDevice() && (
            <span onClick={this.redirectToListView}>
              <i class="i i-close" />
            </span>
          )}
        </div>
        <div class="form-container">
          <Form class={FORM_CLASS_NAME} onChange={this.onFieldChange} onSubmit={this.onSubmit}>
            <main>
              {props.user.isBharatQREnabled && (
                <Input.Radio
                  autoRender
                  label="QR Type"
                  name="type"
                  class="Input--vTop"
                  labelClass="pb-8"
                  options={QR_TYPES}
                  disabled={isSubmitting}
                  defaultValue={QR_TYPES[0].value}
                  onBlur={this.onFieldBlur}
                />
              )}

              <Input.Radio
                autoRender
                label="QR Usage"
                name="usage"
                class="Input--vTop"
                labelClass="pb-8"
                options={USAGE_OPTIONS}
                disabled={isSubmitting}
                defaultValue={USAGE_OPTIONS[0].value}
                onBlur={this.onFieldBlur}
              />

              <Input.Radio
                autoRender
                label="Accept only fixed amount on this QR?"
                name="fixed_amount"
                class="Input--vTop"
                labelClass="pb-8"
                defaultValue={FIXED_AMOUNT_OPTIONS[0].value}
                options={FIXED_AMOUNT_OPTIONS}
                disabled={isSubmitting}
                onBlur={this.onFieldBlur}
              />

              {formData.fixed_amount === '1' && (
                <div class="Input--custom">
                  <Input.Group
                    class="InputGroup--inline"
                    required
                    label={<small class="help-content">Enter the amount</small>}
                  >
                    <div class="Input-content">
                      <Input.CurrencySelect
                        autoRender
                        disabled
                        name="currency"
                        parentQuerySelector=".Modal-body"
                      />
                      <Input
                        autoRender
                        required
                        name="payment_amount"
                        placeholder="0.00"
                        validator={amountValidator}
                        disabled={isSubmitting}
                        onBlur={this.onFieldBlur}
                      />
                    </div>
                  </Input.Group>
                </div>
              )}

              <Input
                autoRender
                name="description"
                label={
                  <>
                    Description <small>(Optional)</small>
                  </>
                }
                class="Input--vTop"
                labelClass="pb-8"
                placeholder="Description will be visible on the QR Code."
                maxLength="120"
                description={
                  <div class="text-right">{(formData.description || '').length} / 120</div>
                }
                disabled={isSubmitting}
                onBlur={this.onFieldBlur}
              />

              <div
                class={classList('additional-options-btn', showAdditionalOptions && 'btn-hide')}
                onClick={this.handleAdditionalOptions}
              >
                <div>
                  <div class="heading">
                    {showAdditionalOptions ? 'Hide' : 'View'} Advance Options
                  </div>
                  <div class="description">QR Name, auto-closing date, and internal notes.</div>
                </div>
                <i
                  class={classList('i', showAdditionalOptions ? 'i-chevron-up' : 'i-chevron-down')}
                />
              </div>

              {showAdditionalOptions && (
                <div class="AdditionalOptions">
                  <Input.Check
                    autoRender
                    label={
                      <>
                        Close By <small>(Optional)</small>
                      </>
                    }
                    fieldLabel="Close this QR code after"
                    labelClass="pb-8"
                    class="Input--vTop"
                    onChange={this.handleHasNoCloseBy}
                  />

                  <Input.Group
                    class="InputGroup--near InputGroup--inline closeBy"
                    disabled={!state.noCloseBy}
                  >
                    <div class="Input-content">
                      <Input.ToCalendar
                        readOnly
                        allowToday
                        disablePastDates
                        placeholder="DD-MM-YYYY"
                        placement="topLeft"
                        size="half"
                        ref={this.closeByRef}
                        onChange={this.onDateChange}
                        addonAfter={<i class="i i-date-range" />}
                      />
                      {!!formData.close_by && (
                        <Input.TimePicker
                          readOnly
                          placeholder="11:59PM"
                          onChange={this.onTimeChange}
                          addonAfter={<i class="i i-time" />}
                        />
                      )}
                    </div>
                  </Input.Group>

                  <div class="Input Input--vTop Input--SelectCustomer">
                    <div class="Input-label pb-8">
                      Customer <small>(Optional)</small>
                    </div>

                    <div class="Input-content">
                      <CustomerSelector
                        disabled={isSubmitting}
                        onChange={this.handleSelectCustomer}
                      />
                    </div>
                  </div>

                  <Input
                    autoRender
                    class="Input--vTop name"
                    name="name"
                    label={
                      <>
                        Name <small>(Optional)</small>
                      </>
                    }
                    description="This will appear on your dashboard."
                    labelClass="pb-8"
                    disabled={isSubmitting}
                    onBlur={this.onFieldBlur}
                  />

                  <Input.PairList
                    class="Input--vTop"
                    name="notes"
                    label="Internal Notes"
                    labelClass="pb-8"
                    onChange={this.onChangeNotes}
                    disabled={isSubmitting}
                    onBlur={this.onFieldBlur}
                  />
                </div>
              )}
            </main>

            <footer>
              {props.isModalView && (
                <Button class="btn-outline" type="button" onClick={props.onClose}>
                  Cancel
                </Button>
              )}

              {isMobileDevice() && !props.isModalView && (
                <Button type="button" onClick={this.redirectToListView}>
                  Cancel
                </Button>
              )}

              <AsyncBtn.Primary
                type="submit"
                pendingState="Creating..."
                onClick={this.onSubmit}
                disabled={isSubmitDisabled}
              >
                Create QR Code
              </AsyncBtn.Primary>
            </footer>
          </Form>
        </div>
      </div>
    );
  }
}

function amountValidator(value) {
  return validateAmount(value);
}
