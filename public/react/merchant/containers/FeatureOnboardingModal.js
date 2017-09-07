import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import AsyncButton from 'react-async-button';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import ModalHeader from 'rzp/ui/ModalHeader';
import * as ModalActions from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';

const selector = formValueSelector('uploadBatch');
@withRouter
@connect(state => ({}), { ...ModalActions, showNotification })
@reduxForm({
  form: 'featureOnboardingModal',
})
export default class FeatureOnboardingModal extends Component {
  // Form submit handler
  onSubmitClick = props => {
    const prom = new Promise(() => {
      let additionalFormFields = {};

      this.props
        // do something
        .then(() => {
          this.props.closeModal();

          this.props.showNotification({
            type: 'success',
            message: 'Successful',
          });
          this.props.history.push(this.props.closeUrl);
        })
        .catch(({ errors }) => {
          this.props.showNotification({
            type: 'error',
            message: errors,
          });
        });
    });

    return prom;
  };

  // View render
  render() {
    const { handleSubmit } = this.props;

    return (
      <div class="feature-onboarding-modal">
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
