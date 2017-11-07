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
import { required, phone, email, amount } from 'rzp/utils/validators';
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
        contact: 'Please enter a contact or email',
      };
    }
  }

  if (values.sms_notify && (isBlank(customer) || isBlank(customer.contact))) {
    errors.customer = {
      contact: 'Please enter a number',
    };
  }

  if (values.email_notify && (isBlank(customer) || isBlank(customer.email))) {
    errors.customer = {
      email: 'Please enter an email',
    };
  }

  return errors;
}

@connect(
  state => {
    return {
      ...state.session,
      expireBy: selector(state, 'expire_by'),
      // `expireByDate` is the expiry date wihtout any info about the time
      // (start of the day)
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
      email_notify: false,
      sms_notify: false,
    };
    this.setExpiryDate = this.setExpiryDate.bind(this);
    this.handleCommChange = this.handleCommChange.bind(this);
  }

  componentWillMount() {
    if (this.props.invoice) {
      const expireBy = this.props.invoice.expire_by;

      this.props.initialize({
        ...this.props.invoice,
        ...(expireBy && {
          expire_by_date: moment(expireBy * 1000)
            .startOf('day')
            .unix(),
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
          (expiryWithTime -
            moment(expiryWithTime)
              .startOf('day')
              .valueOf());
      } else {
        // if `expiryDateWithTime` is not set and somebody selects a date
        // expiry time should be the EOD of the selected date (11:59 PM)
        // `date` will always be the start of the day
        expiryWithTime = (date + 24 * 60 * 60 - 1) * 1000;
      }
    } else {
      // Remove time field when date field is unset
      expiryWithTime = null;
    }

    this.props.change('expire_by', expiryWithTime);
  }
  //Set communication mode(SMS or EMAIL)
  handleCommChange(value, mode) {
    var commStr = mode === 'p' ? 'sms_notify' : 'email_notify';
    this.setState({ [commStr]: !!value.length });
    return this.props.change(commStr, !!value.length);
  }

  save = props => {
    const params = { ...props };
    let notificationMSG = 'Payment link created successfully.',
      notifyMedium = [];

    if (props.sms_notify) {
      notifyMedium.push('SMS');
    }

    if (props.email_notify) {
      notifyMedium.push('Email');
    }

    if (notifyMedium.length > 0) {
      notificationMSG += ' Sending via ' + notifyMedium.join(' and ');
    }

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
          message: notificationMSG,
        });
      })
      .catch(({ errors }) => {
        this.setState({
          errors,
        });
      });
  };

  generateCtaText = () => {
    let { invoice } = this.props;
    let { sms_notify, email_notify } = this.state;
    let ctaText = {};

    if (invoice) {
      ctaText = { btn: 'Save Payment Link', pending: 'Saving...' };
    } else {
      if (email_notify || sms_notify) {
        ctaText = { btn: 'Send Payment Link', pending: 'Sending...' };
      } else {
        ctaText = { btn: 'Create Payment Link', pending: 'Creating...' };
      }
    }

    return ctaText;
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
    let ctaText = this.generateCtaText();

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
            {isNewForm && (
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
                      validate={[
                        required('Please enter the amount'),
                        amount('Please enter a valid amount (example 123.45)'),
                      ]}
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
                      validate={required('Please enter the summary')}
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
                  {this.props.expireBy && (
                    <div class="col-md-4">
                      <Field
                        name="expire_by"
                        disabled={!invoice || !invoice.expire_by_date}
                        component={ReduxDatetime}
                        dateFormat={false}
                        timeFormat={true}
                      />
                    </div>
                  )}
                </div>
              </div>
            )}

            <div class="form-group customer">
              <label class="col-md-3 control-label">Customer</label>
              <div class="col-md-4">
                <Field
                  name="customer[contact]"
                  component={InputField}
                  class="form-control"
                  placeholder="Phone"
                  validate={phone('Please enter a valid number')}
                  disabled={isEdit}
                  onChange={e => {
                    this.handleCommChange(e.target.value, 'p');
                  }}
                />
              </div>

              <div class="col-md-4 or-separator">
                <Field
                  name="customer[email]"
                  component={InputField}
                  class="form-control"
                  placeholder="Email"
                  validate={email('Please enter a valid email')}
                  disabled={isEdit}
                  onChange={e => {
                    this.handleCommChange(e.target.value, 'e');
                  }}
                />
              </div>
            </div>

            {!isNewForm && (
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
                      validate={required('Please enter a product/service name')}
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
                      validate={required('Please enter the amount')}
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
              </div>
            )}

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

            {isTestMode && (
              <div class="row">
                <div class="col-md-8 col-md-offset-3">
                  <div class="alert alert-sm alert-warning">
                    You are creating the link in <b>Test Mode</b>
                    . So, only test payments can be made for this link.
                    {/* Also, SMS will not be sent in test mode */}
                  </div>
                </div>
              </div>
            )}
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
              text={ctaText.btn}
              pendingText={ctaText.pending}
              onClick={handleSubmit(this.save)}
            />
          </div>
        </form>
      </div>
    );
  }
}
