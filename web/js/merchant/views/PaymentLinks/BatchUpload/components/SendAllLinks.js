import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import ModalHeader from 'common/ui/ModalHeader';
import CheckboxField from 'common/ui/Forms/CheckboxField';
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { notifyBatch, notifyPaymentPageBatch } from 'merchant/reducers/batches';

const PAYMENT_PAGE = `payment_page`;

@connect((state) => state.session, {
  showNotification,
  closeModal,
  notifyBatch,
  notifyPaymentPageBatch,
})
@reduxForm({
  form: 'SendAllLinks',
  initialValues: {
    sms_notify: 0,
    email_notify: 0,
  },
})
export default class SendAllLinksModal extends Component {
  sendLinks = (props) => {
    const {
      type,
      notifyBatch,
      notifyPaymentPageBatch,
      paymentLinkId,
      batchId,
      user,
      trackSendAllLinks,
      showNotification,
      closeModal,
      fetchAll,
    } = this.props;
    console.log('sendLinks:', type);
    let fetchAllType = '';
    const notifyFn =
      type === PAYMENT_PAGE
        ? notifyPaymentPageBatch({ id: paymentLinkId, batchId, data: props })
        : notifyBatch(batchId, props);
    if (type !== PAYMENT_PAGE) {
      fetchAllType = user.isPaymentlinksV2Enabled ? 'payment_link_v2' : 'payment_link';
    }
    trackSendAllLinks({
      ...props,
      batch_id: batchId,
    });

    return notifyFn
      .then(() => {
        showNotification({
          type: 'success',
          message: 'All payment links of this batch will be sent shortly',
        });
        closeModal();

        //re-render the list
        fetchAll({
          skip: 0,
          count: 25,
          type: fetchAllType,
        });
      })
      .catch(({ errors }) => {
        showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  render() {
    const { handleSubmit, mode } = this.props;

    return (
      <div class="issue-invoice-modal">
        <ModalHeader title="Send Reminder?" onCloseClick={this.props.closeModal} />

        <form class="form-horizontal">
          <div class="modal-body">
            <p>Are you sure that you want to send the unpaid links again? </p>
            <div class="rzpCheckbox next">
              <Field name="sms_notify" id="sms_notify" component={CheckboxField} type="checkbox" />
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
                <span>Unpaid Links include the links that are issued but not paid.</span>
              </small>
            </div>
            {mode === 'test' && (
              <div class="alert alert-sm alert-warning">
                Payment links were created in <b>Test Mode</b>. So, only test payments can be made.
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
