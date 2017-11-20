import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import AsyncButton from 'react-async-button';
import ModalHeader from 'rzp/ui/ModalHeader';
import Clipboard from 'rzp/ui/Clipboard';
import { titleCase } from 'rzp/utils/rzp-utils';
import * as ModalActions from 'rzp/modules/modals';

const selector = formValueSelector('issueInvoice');
@connect(state => {
  return {
    session: state.session,
    sms_notify: selector(state, 'sms_notify'),
    email_notify: selector(state, 'email_notify'),
  };
}, ModalActions)
@reduxForm({
  form: 'issueInvoice',
})
export default class IssueInvoiceConfirmModal extends Component {
  constructor() {
    super(...arguments);
    this.state = {
      paymentLink: '',
    };
  }

  componentWillMount() {
    let customer = this.props.customer;
    if (customer) {
      this.props.initialize({
        sms_notify: !!customer.contact,
        email_notify: !!customer.email,
      });
    }
  }

  componentDidMount() {
    this.props.onMount && this.props.onMount();
  }

  componentWillUnmount() {
    this.props.onUnmount && this.props.onUnmount();
  }

  onIssueClick = props => {
    return this.props
      .onIssue(props)
      .then(invoice => {
        if (props.sms_notify || props.email_notify) {
          this.props.closeModal();
        } else {
          this.setState({
            paymentLink: invoice.short_url,
          });
        }
      })
      .catch(() => {
        this.props.closeModal();
      });
  };

  render() {
    const {
      handleSubmit,
      customer,
      sms_notify,
      email_notify,
      isPaymentLink,
      disableIssueOnEmptySelection,
    } = this.props;

    const isTestMode = this.props.session.mode === 'test';
    const paymentLink = this.state.paymentLink;
    let entityName = isPaymentLink ? 'payment link' : 'invoice';
    let disabled =
      (disableIssueOnEmptySelection || isPaymentLink) &&
      !(sms_notify || email_notify);

    return (
      <div class="issue-invoice-modal">
        <ModalHeader
          title={
            isPaymentLink
              ? 'Send Link'
              : paymentLink ? 'Issued' : 'Issue Invoice'
          }
          onCloseClick={this.props.closeModal}
        />

        <form class="form-horizontal">
          <div class="modal-body">
            {paymentLink ? (
              <div>
                <p class="help-block">
                  Share the following link with the customer manually to receive
                  the payment
                </p>
                <Clipboard value={paymentLink} />

                <div class="Modal__actions">
                  <AsyncButton
                    type="submit"
                    class="btn btn-primary btn-block btn-lg"
                    text="Done"
                    onClick={this.props.closeModal}
                  />
                </div>
              </div>
            ) : (
              <div>
                <p>
                  Send {titleCase(entityName)} and payment instructions to...
                </p>
                {customer.contact && (
                  <div class="rzpCheckbox">
                    <Field
                      name="sms_notify"
                      id="sms_notify"
                      component="input"
                      type="checkbox"
                    />
                    <label for="sms_notify">{customer.contact}</label>
                  </div>
                )}

                {customer.email && (
                  <div class="rzpCheckbox">
                    <Field
                      name="email_notify"
                      id="email_notify"
                      component="input"
                      type="checkbox"
                    />
                    <label for="email_notify">{customer.email}</label>
                  </div>
                )}

                {!isPaymentLink && (
                  <div>
                    A <b>payment link</b> will also be created.
                  </div>
                )}

                <div>
                  The {entityName} can <b>not</b> be edited after issuing.
                </div>

                {isTestMode && (
                  <div class="alert alert-sm alert-warning">
                    The {entityName} is created in <b>Test Mode</b>
                    . So, only test payments can be made for this {entityName}
                    .
                    {/* Also, SMS will not be sent in test mode.*/}
                  </div>
                )}

                <div class="Modal__actions">
                  <AsyncButton
                    type="submit"
                    class="btn btn-primary btn-block btn-lg"
                    text={isPaymentLink ? 'Send Link' : 'Issue Invoice'}
                    pendingText={isPaymentLink ? 'Sending...' : 'Issuing...'}
                    disabled={disabled}
                    onClick={handleSubmit(this.onIssueClick)}
                  />
                </div>
              </div>
            )}
          </div>
        </form>
      </div>
    );
  }
}
