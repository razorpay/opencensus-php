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
import { saveOnboarding } from 'merchant/modules/onboarding';

const selector = formValueSelector('uploadBatch');
@withRouter
@connect(null, {
  saveOnboarding,
  ...ModalActions,
  showNotification,
})
@reduxForm({
  form: 'featureOnboardingModal',
})
export default class FeatureOnboardingModal extends Component {
  // Form submit handler
  onSubmitClick = props => {
    const prom = new Promise(() => {
      let data = {};

      data[this.props.feature] = props;

      this.props
        .saveOnboarding(data)
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
            <div class="form-group">
              <label for="use_case">Use Case</label>
              <Field
                name="use_case"
                component={AutoResizeTextarea}
                rows="3"
                class="form-control"
                placeholder="Your use case for the product and business model"
              />
            </div>

            <div class="form-group">
              <label for="email_notify">Transfer for</label>
              <Field
                name="settling_to"
                component={InputField}
                tagName="select"
                class="form-control"
                placeholder="Transferring Payments to?"
                validate={[required()]}
              >
                <option value="Businesses" key="vendors">
                  Third-party businesses
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

      case 'subscriptions':
        return (
          <div>
            <div class="form-group">
              <label for="use_case">Use Case</label>
              <Field
                name="use_case"
                component={AutoResizeTextarea}
                rows="3"
                class="form-control"
                placeholder="Your use case for the product and business model"
              />
            </div>

            <div class="form-group">
              <label for="email_notify">Transfer for</label>
              <Field
                name="settling_to"
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

      case 'virtual_accounts':
        return (
          <div>
            <div>
              <label for="use_case">Use Case</label>
              <Field
                name="use_case"
                component={AutoResizeTextarea}
                rows="3"
                class="form-control"
                placeholder="Your use case for virtual accounts"
              />
            </div>

            <div>
              <label for="expected_monthly_revenue">
                Expected Monthly Revenue
              </label>
              <Field
                name="expected_monthly_revenue"
                component={InputField}
                rows="2"
                class="form-control"
                placeholder="Expected monthly revenue through virtual accounts"
                validate={[required()]}
              />
            </div>
          </div>
        );
    }
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
              <p>
                We require a few details to enable this feature on your account.
              </p>
            </div>

            {this.getQuestionsElements()}

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
          }
        </form>
      </div>
    );
  }
}
