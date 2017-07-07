import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import AsyncButton from 'react-async-button';

import { Field, reduxForm, formValueSelector } from 'redux-form';
import ModalHeader from 'rzp/ui/ModalHeader';
import * as ModalActions from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';

const selector = formValueSelector('uploadBatch');
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
    let additionalFormFields = {};

    additionalFormFields['sms_notify'] = props.sms_notify === true ? '1' : '0';
    additionalFormFields['email_notify'] = props.email_notify === true
      ? '1'
      : '0';
    additionalFormFields['draft'] = '0'; // Implicitly sending draft = '0'

    this.props
      .submitUploadBatch(additionalFormFields)
      .then(() => {
        this.props.closeModal();
        this.props.showNotification({
          type: 'success',
          message: 'Successful',
        });
      })
      .catch(({ errors }) => {
        this.props.closeModal();
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
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
              <p>
                Send instructions to...
              </p>

              <div class="rzpCheckbox">
                <Field
                  name="sms_notify"
                  id="sms_notify"
                  component="input"
                  type="checkbox"
                />
                <label for="sms_notify">Sms Notify</label>
              </div>

              <div class="rzpCheckbox">
                <Field
                  name="email_notify"
                  id="email_notify"
                  component="input"
                  type="checkbox"
                />
                <label for="email_notify">Email Notify</label>
              </div>
            </div>

            <div class="Modal__actions">
              <AsyncButton
                type="submit"
                class="btn btn-primary btn-block btn-lg"
                text="Submit"
                pendingText="Submitting"
                onClick={handleSubmit(this.onSubmitClick)}
              />
            </div>
          </div>
        </form>
      </div>
    );
  }
}
