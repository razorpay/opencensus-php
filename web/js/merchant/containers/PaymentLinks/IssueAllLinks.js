import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import ModalHeader from 'rzp/ui/ModalHeader';
import CheckboxField from 'rzp/ui/Forms/CheckboxField';
import { closeModal } from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';
import {
  issuePaymentLinkBatch,
  editIssuableBatchList,
} from 'merchant/modules/batches';

@connect(state => state.session, {
  showNotification,
  closeModal,
  issuePaymentLinkBatch,
  editIssuableBatchList,
})
@reduxForm({
  form: 'issueAllLinks',
  initialValues: {
    sms_notify: 0,
    email_notify: 0,
  },
})
export default class IssueAllLinksModal extends Component {
  issue = props => {
    return this.props
      .issuePaymentLinkBatch(this.props.batchId, props)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'All payment links of this batch will be issued shortly',
        });
        this.props.closeModal();
        this.props.editIssuableBatchList(this.props.batchId); // For refreshing UI (will remove 'Issue all links' Btn corresponding to this batch id as it's success)
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
          title="Issue all payment links?"
          onCloseClick={this.props.closeModal}
        />

        <form class="form-horizontal">
          <div class="modal-body">
            <p>Are you sure to issue all links? </p>
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
                text="Yes, Issue All"
                pendingText="Issuing..."
                onClick={handleSubmit(this.issue)}
              />
            </div>
          </div>
        </form>
      </div>
    );
  }
}
