import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import AsyncButton from 'react-async-button';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import ModalHeader from 'rzp/ui/ModalHeader';
import * as ModalActions from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';
import AutoResizeTextarea from 'rzp/ui/Forms/AutoResizeTextarea';
import InputField from 'rzp/ui/Forms/InputField';
import { required } from 'rzp/utils/validators';

const selector = formValueSelector('uploadBatch');
@withRouter
@connect(state => ({}), { ...ModalActions, showNotification })
@reduxForm({
  form: 'featureOnboardingModal',
})
export default class FeatureOnboardingModal extends Component {
  // Form submit handler
  onSubmitClick = props => {
    console.log('asd');
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

  getQuestionsElements() {
    switch (this.props.feature) {
      case 'marketplace':
        return (
          <div>
            <div class="value">
              <label for="use_case">Use Case</label>
              <Field
                name="use_case"
                component={AutoResizeTextarea}
                rows="2"
                class="form-control"
                placeholder="Your use case for the product and business model"
              />
            </div>

            <div class="value">
              <label for="email_notify">Transfer for</label>
              <Field
                name="bank_beneficiary_state"
                component={InputField}
                tagName="select"
                class="form-control"
                placeholder="Transferring Payments to?"
                validate={[required()]}
              >
                <option value="Businesses" key="vendors">
                  Third party businesses
                </option>
                <option value="Own Accounts" key="own_accounts">
                  Own bank accounts
                </option>
                <option value="Individuals" key="individuals">
                  Individuals
                </option>
              </Field>
            </div>
          </div>
        );

      case 'susbcriptions':
        return 'asd';

      case 'virtual_accounts':
        return (
          <div>
            <div class="value">
              <label for="use_case">Use Case</label>
              <Field
                name="use_case"
                component={AutoResizeTextarea}
                rows="2"
                class="form-control"
                placeholder="Your use case for the product and business model"
              />
            </div>

            <div class="value">
              <label for="use_case">Use Case</label>
              <Field
                name="use_case"
                component={AutoResizeTextarea}
                rows="2"
                class="form-control"
                placeholder="Your use case for the product and business model"
              />
            </div>
          </div>
        );
    }

    return 'asds';
  }

  // View render
  render() {
    const { handleSubmit } = this.props;

    return (
      <div class="feature-onboarding-modal">
        <ModalHeader
          title="Feature Request"
          onCloseClick={this.props.closeModal}
        />
        <form class="form-horizontal">
          <div class="modal-body">
            <div>
              <p>Provide the following details for the request.</p>

              {this.getQuestionsElements()}
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
