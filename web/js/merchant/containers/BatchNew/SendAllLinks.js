import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import ModalHeader from 'rzp/ui/ModalHeader';
import CheckboxField from 'rzp/ui/Forms/CheckboxField';
import { closeModal } from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';
import { notifyBatch } from 'merchant/modules/batches';
import { trackSendAllLinks } from './ga';

@connect(state => state.session, {
  showNotification,
  closeModal,
  notifyBatch,
})
@reduxForm({
  form: 'SendAllLinks',
  initialValues: {
    sms_notify: 0,
    email_notify: 0,
  },
})
export default class SendAllLinksModal extends Component {
  sendLinks = props => {
    trackSendAllLinks({
      ...props,
      batch_id: this.props.batchId,
    });
    return this.props
      .notifyBatch(this.props.batchId, props)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'All payment links of this batch will be sent shortly',
        });
        this.props.closeModal();

        //re-render the list
        this.props.fetchAll({
          skip: 0,
          count: 25,
          type: 'payment_link',
        });
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  render() {
    const { handleSubmit, mode } = this.props;

    return (
      <div class="issue-invoice-modal">
        <ModalHeader
          title="Send Reminder?"
          onCloseClick={this.props.closeModal}
        />

        <form class="form-horizontal">
          <div class="modal-body">
            <p>Are you sure that you want to send the unpaid links again? </p>
            <div class="rzpCheckbox next">
              <Field
                name="sms_notify"
                id="sms_notify"
                component={CheckboxField}
                type="checkbox"
              />
              <label for="sms_notify" class="icon i-check">
                Send SMS
              </label>
            </div>

            <div class="rzpCheckbox next">
              <Field
                name="email_notify"
                id="email_notify"
                component={CheckboxField}
                type="checkbox"
              />
              <label for="email_notify" class="icon i-check">
                Send Email
              </label>
            </div>
            <div>
              <small class="help-block">
                <i class="i i-info-circle" />
                <span>
                  Unpaid Links include the links that are issued but not paid.
                </span>
              </small>
            </div>
            {mode === 'test' && (
              <div class="alert alert-sm alert-warning">
                Payment links were created in <b>Test Mode</b>
                . So, only test payments can be made.
                {/* Also, SMS will not be sent in test mode. */}
              </div>
            )}
            <div class="Modal__actions">
              <AsyncButton
                type="submit"
                class="btn btn-primary btn-block btn-lg"
                text="Yes, Send All"
                pendingText="Sending..."
                onClick={handleSubmit(this.sendLinks)}
              />
            </div>
          </div>
        </form>
      </div>
    );
  }
}
