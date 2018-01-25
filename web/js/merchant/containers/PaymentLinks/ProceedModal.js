import { Component } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import AsyncButton from 'react-async-button';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import ModalHeader from 'rzp/ui/ModalHeader';
import * as ModalActions from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';

const selector = formValueSelector('uploadBatch');
@withRouter
@connect(
  state => ({
    sms_notify: selector(state, 'sms_notify'),
    email_notify: selector(state, 'email_notify'),
  }),
  { ...ModalActions, showNotification }
)
@reduxForm({
  form: 'uploadBatch',
})
export default class ProceedFormFields extends Component {
  onSubmitClick = props => {
    const prom = new Promise(() => {
      let additionalFormFields = {};

      additionalFormFields['sms_notify'] =
        props.sms_notify === true ? '1' : '0';
      additionalFormFields['email_notify'] =
        props.email_notify === true ? '1' : '0';
      additionalFormFields['draft'] = '0'; // Implicitly sending draft = '0'

      this.props
        .submitUploadBatch(additionalFormFields)
        .then(() => {
          this.props.closeModal();
          this.props.showNotification({
            type: 'success',
            message: 'Successful',
          });
          this.props.history.push(this.props.closeUrl);
        })
        .catch(({ errors }) => {
          this.props.closeModal();
          this.props.showNotification({
            type: 'error',
            message: errors,
          });
        });
    });

    return prom;
  };

  render() {
    const { handleSubmit } = this.props;

    return (
      <div class="proceed-upload-modal">
        <ModalHeader
          title="Batch Upload"
          onCloseClick={this.props.closeModal}
        />
        <form class="form-horizontal">
          <div class="modal-body">
            <div>
              <p>Send link and payment instructions to...</p>

              <div class="rzpCheckbox">
                <Field
                  name="sms_notify"
                  id="sms_notify"
                  component="input"
                  type="checkbox"
                />
                <label for="sms_notify" class="i i-check">
                  Sms Notify
                </label>
              </div>

              <div class="rzpCheckbox">
                <Field
                  name="email_notify"
                  id="email_notify"
                  component="input"
                  type="checkbox"
                />
                <label for="email_notify" class="i i-check">
                  Email Notify
                </label>
              </div>
            </div>

            <div>
              A <b>payment link</b> will also be created.
            </div>

            <div class="Modal__actions">
              <AsyncButton
                type="submit"
                class="btn btn-primary btn-block btn-lg"
                text="Submit"
                pendingText="Submitting..."
                onClick={handleSubmit(this.onSubmitClick)}
              />
            </div>
          </div>
        </form>
      </div>
    );
  }
}
