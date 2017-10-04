import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, FieldArray, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';
import moment from 'moment';
import DatePickerField from 'rzp/ui/Forms/DatePickerField';
import ReduxDatetime from 'rzp/ui/ReduxDatetime';
import InputField from 'rzp/ui/Forms/InputField';
import ModalHeader from 'rzp/ui/ModalHeader';
import Alert from 'rzp/ui/Forms/Alert';
import { isBlank } from 'rzp/utils/rzp-utils';
import { saveInvoice } from 'merchant/modules/invoices/list';
import { required, phone, email } from 'rzp/utils/validators';
import { showNotification } from 'rzp/modules/notifications';
import ShowWhen from 'merchant/components/ShowWhen';
import NotesFieldArray from 'merchant/components/NotesFieldArray';

const selector = formValueSelector('newPaymentLink');

function validate(values) {
  let errors = {};
  let customer = values.customer;
  let isNewForm = isBlank(values.line_items);

  if (!isNewForm) {
    if (
      isBlank(customer) ||
      (isBlank(customer.contact) && isBlank(customer.email))
    ) {
      errors.customer = {
        contact: 'Please provide contact or email',
      };
    }
  }

  if (values.sms_notify && (isBlank(customer) || isBlank(customer.contact))) {
    errors.customer = {
      contact: 'Please provide contact',
    };
  }

  if (values.email_notify && (isBlank(customer) || isBlank(customer.email))) {
    errors.customer = {
      email: 'Please provide email',
    };
  }

  return errors;
}

@connect(
  state => {
    return {
      ...state.session,
      expireBy: selector(state, 'expire_by'),
      expireByDate: selector(state, 'expire_by_date'),
    };
  },
  { saveInvoice, showNotification }
)
@reduxForm({
  form: 'newPaymentLink',
  initialValues: {
    type: 'link',
  },
  validate,
})
export default class CreatePaymentLink extends Component {
  static contextTypes = {
    session: PropTypes.object,
  };

  constructor() {
    super(...arguments);
    this.state = {
      errors: null,
    };
    this.setExpiryDate = this.setExpiryDate.bind(this);
  }

  componentWillMount() {
    if (this.props.invoice) {
      const expireBy = this.props.invoice.expire_by;

      this.props.initialize({
        ...this.props.invoice,
        ...(expireBy && {
          expire_by_date: moment(expireBy * 1000).startOf('day').unix(),
          expire_by: expireBy * 1000,
        }),
      });
    }
  }

  setExpiryDate(date) {
    /*
     * This gets executed when Expire By date is set/removed
     * In the case of removal, `date` will be null
     **/

    // this.props.expireBy will contain the time that is stored in the
    // backend
    let expiryWithTime = this.props.expireBy;

    if (date) {
      if (expiryWithTime) {
        // calculate time elapsed since the start of `expiryDateWithTime`
        // and add the diff to selected date
        expiryWithTime =
          date * 1000 +
          (expiryWithTime - moment(expiryWithTime).startOf('day').valueOf());
      } else {
        // if `expiryDateWithTime` is not set and somebody selects a date
        // expiry time should be the EOD of the selected date (11:59 PM)
        expiryWithTime = date * 1000 + 24 * 60 * 60 * 1000 - 1000;
      }
    } else {
      // Remove time field when date field is unset
      expiryWithTime = null;
    }

    this.props.change('expire_by', expiryWithTime);
  }

  save = props => {
    const params = { ...props };

    if (params.expire_by) {
      if (
        typeof params.expire_by === 'number' ||
        moment.isMoment(params.expire_by)
      ) {
        params.expire_by = window.parseInt(params.expire_by / 1000);
      } else {
        return this.setState({ errors: ['Invalid Expiry Date'] });
      }
    }

    return this.props
      .saveInvoice(params)
      .then(invoice => {
        this.props.onSave(invoice);
        this.props.closeModal();
        this.props.showNotification({
          type: 'success',
          message: 'Payment link saved successfully',
        });
      })
      .catch(({ errors }) => {
        this.setState({
          errors,
        });
      });
  };

  render() {
    const { handleSubmit, invoice } = this.props;
    let isTestMode = this.props.mode === 'test';
    let isNewForm = !(invoice && !isBlank(invoice.line_items));
    let isEdit = !!invoice;
    let status = invoice && invoice.status;
    let isPaid = status === 'paid';
    let isCancelled = status === 'cancelled';
    let isExpired = status === 'expired';
    let locked = isPaid || isExpired || isCancelled;

    return (
      <div>
        <ModalHeader
          title={isEdit ? 'Edit Payment Link' : 'Create Payment Link'}
          onCloseClick={this.props.closeModal}
        />

        <form
          class="form-horizontal payment-link-form"
          onSubmit={handleSubmit(this.save)}
        >
          <div class="modal-body">
            <Alert type="error" message={this.state.errors} />
            {isNewForm &&
              <div>
                <div class="form-group">
                  <label class="col-md-3 control-label help-label label-required">
                    <div>Amount</div>
                    <small>(in INR)</small>
                  </label>
                  <div class="col-md-8">
                    <Field
                      name="amountInINR"
                      component={InputField}
                      class="form-control"
                      autoFocus={true}
                      validate={required('Please provide the amount')}
                      disabled={isEdit}
                    />
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-3 control-label help-label label-required">
                    Summary
                  </label>
                  <div class="col-md-8">
                    <Field
                      name="description"
                      component={InputField}
                      tagName="textarea"
                      type="textarea"
                      class="form-control"
                      validate={required('Please provide the description')}
                      disabled={isEdit}
                    />
                  </div>
                </div>

                <ShowWhen featureEnabled="Invoice_Partial_Payments">
                  <div class="form-group">
                    <div class="col-md-8 col-md-offset-3">
                      <div class="rzpCheckbox rzpCheckbox-sm">
                        <Field
                          name="partial_payment"
                          id="partial_payment"
                          component="input"
                          type="checkbox"
                          disabled={locked}
                        />
                        <label for="partial_payment">
                          Enable Partial Payments
                        </label>
                      </div>
                    </div>
                  </div>
                </ShowWhen>

                <div class="form-group">
                  <label class="col-md-3 control-label help-label">
                    Receipt No.
                  </label>
                  <div class="col-md-8">
                    <Field
                      name="receipt"
                      component="input"
                      class="form-control"
                    />
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-3 control-label help-label">
                    Expire By
                  </label>
                  <div class="col-md-4">
                    <Field
                      name="expire_by_date"
                      component={DatePickerField}
                      startOfDayTimeStamp={true}
                      showClearDate={true}
                      isOutsideRange={day => {
                        let diff = moment().diff(day, 'hours') / 24;
                        return Math.floor(diff) > 0;
                      }}
                      onDateChange={this.setExpiryDate}
                    />
                  </div>
                  {this.props.expireBy &&
                    <div class="col-md-4">
                      <Field
                        name="expire_by"
                        disabled={!invoice || !invoice.expire_by_date}
                        component={ReduxDatetime}
                        dateFormat={false}
                        timeFormat={true}
                      />
                    </div>}
                </div>
              </div>}

            <div class="form-group customer">
              <label class="col-md-3 control-label">Customer</label>
              <div class="col-md-4">
                <Field
                  name="customer[contact]"
                  component={InputField}
                  class="form-control"
                  placeholder="Customer phone"
                  validate={phone('Please provide valid contact number')}
                  disabled={isEdit}
                />
              </div>

              <div class="col-md-4 or-separator">
                <Field
                  name="customer[email]"
                  component={InputField}
                  class="form-control"
                  placeholder="Customer email"
                  validate={email('Please provide valid email')}
                  disabled={isEdit}
                />
              </div>
            </div>

            {!isNewForm &&
              <div>
                <div class="form-group">
                  <label class="col-md-3 control-label help-label">
                    <div>Item Name</div>
                    <small>Product/Service</small>
                  </label>
                  <div class="col-md-8">
                    <Field
                      name="line_items[0][name]"
                      component={InputField}
                      class="form-control"
                      validate={required('Please provide product/service name')}
                      disabled={isEdit}
                    />
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-3 control-label help-label">
                    <div>Amount</div>
                    <small>(in INR)</small>
                  </label>
                  <div class="col-md-8">
                    <Field
                      name="line_items[0][amount]"
                      component={InputField}
                      class="form-control"
                      validate={required('Please provide the amount')}
                      disabled={isEdit}
                    />
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-3 control-label help-label">
                    Receipt No.
                  </label>
                  <div class="col-md-8">
                    <Field
                      name="receipt"
                      component="input"
                      class="form-control"
                    />
                  </div>
                </div>
              </div>}

            <div class="form-group">
              <label class="col-md-3 control-label">Notify Customer</label>
              <div class="col-md-8">
                <label class="checkbox-inline">
                  <Field
                    name="sms_notify"
                    component="input"
                    type="checkbox"
                    disabled={isEdit}
                  />
                  SMS
                </label>
                <label class="checkbox-inline">
                  <Field
                    name="email_notify"
                    component="input"
                    type="checkbox"
                    disabled={isEdit}
                  />
                  Email
                </label>
              </div>
            </div>

            <div class="form-group">
              <label class="col-md-3 control-label">Add Internal Notes</label>
              <div class="col-md-8">
                <FieldArray
                  name="notes"
                  component={NotesFieldArray}
                  nonEditableUptilIndex={
                    invoice ? invoice.notes.length - 1 : -1
                  }
                  required
                />
              </div>
            </div>

            {isTestMode &&
              <div class="row">
                <div class="col-md-8 col-md-offset-3">
                  <div class="alert alert-sm alert-warning">
                    You are creating the link in <b>Test Mode</b>
                    . So, only test payments can be made for this link.
                    {/* Also, SMS will not be sent in test mode */}
                  </div>
                </div>
              </div>}
          </div>

          <div class="modal-footer">
            <button
              type="button"
              class="btn btn-default"
              onClick={this.props.closeModal}
            >
              Cancel
            </button>

            <AsyncButton
              type="submit"
              class="btn btn-primary"
              text="Save"
              pendingText="Saving..."
              onClick={handleSubmit(this.save)}
            />
          </div>
        </form>
      </div>
    );
  }
}
