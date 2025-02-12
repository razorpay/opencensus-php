import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import ModalHeader from 'common/ui/ModalHeader';
import CheckboxField from 'common/ui/Forms/CheckboxField';
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { notifyBatch, notifyPaymentPageBatch } from 'merchant/reducers/batches';
import { compose } from 'redux';

const PAYMENT_PAGE = `payment_page`;

class SendAllLinksModal extends Component {
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
      <div className="issue-invoice-modal">
        <ModalHeader title="Send Reminder?" onCloseClick={this.props.closeModal} />

        <form className="form-horizontal">
          <div className="modal-body">
            <p>Are you sure that you want to send the unpaid links again? </p>
            <div className="rzpCheckbox next">
              <Field name="sms_notify" id="sms_notify" component={CheckboxField} type="checkbox" />
              <label htmlFor="sms_notify" className="icon i-check">
                Send SMS
              </label>
            </div>

            <div className="rzpCheckbox next">
              <Field
                name="email_notify"
                id="email_notify"
                component={CheckboxField}
                type="checkbox"
              />
              <label htmlFor="email_notify" className="icon i-check">
                Send Email
              </label>
            </div>
            <div>
              <small className="help-block">
                <i className="i i-info-circle" />
                <span>Unpaid Links include the links that are issued but not paid.</span>
              </small>
            </div>
            {mode === 'test' && (
              <div className="alert alert-sm alert-warning">
                Payment links were created in <b>Test Mode</b>. So, only test payments can be made.
                {/* Also, SMS will not be sent in test mode. */}
              </div>
            )}
            <div className="Modal__actions">
              <AsyncButton
                type="submit"
                className="btn btn-primary btn-block btn-lg"
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

export default compose(
  connect((state) => state.session, {
    showNotification,
    closeModal,
    notifyBatch,
    notifyPaymentPageBatch,
  }),
  reduxForm({
    form: 'SendAllLinks',
    initialValues: {
      sms_notify: 0,
      email_notify: 0,
    },
  }),
)(SendAllLinksModal);
