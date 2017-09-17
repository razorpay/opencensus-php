import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';

import AsyncButton from 'react-async-button';
import { Field, reduxForm, formValueSelector } from 'redux-form';
import { showNotification } from 'rzp/modules/notifications';
import AutoResizeTextarea from 'rzp/ui/Forms/AutoResizeTextarea';
import FileUploadInputButton from 'rzp/ui/FileUpload/InputButton';
import InputField from 'rzp/ui/Forms/InputField';
import { required } from 'rzp/utils/validators';
import {
  saveOnboarding,
  getOnboardingResponse,
} from 'merchant/modules/onboarding';

const selector = formValueSelector('uploadBatch');
@withRouter
@connect(null, {
  saveOnboarding,
  getOnboardingResponse,
  showNotification,
})
@reduxForm({
  form: 'featureOnboarding',
})
export default class FeatureOnboarding extends Component {
  state = {
    submitted: false,
    isLoading: false,
    uploadedFile: null,
  };

  componentWillMount() {
    this.setState({
      isLoading: true,
    });

    this.props
      .getOnboardingResponse(this.props.feature)
      .then(responses => {
        var submitted = responses.data && !(responses.data instanceof Array);

        this.setState({
          isLoading: false,
          submitted: submitted,
        });
      })
      .catch
      // handle errors
      ();
  }

  handleChange = event => {
    this.setState({ uploadedFile: event.target.files[0] });
  };

  // Form submit handler
  onSubmitClick = props => {
    const prom = new Promise(() => {
      let data = {};
      let file = null;
      let fileName = null;

      if (this.state.uploadedFile && this.props.feature === 'marketplace') {
        file = this.state.uploadedFile;
        fileName = 'vendor_agreement';
      }

      return this.props
        .saveOnboarding(this.props.feature, props, file, fileName)
        .then(() => {
          this.props.showNotification({
            type: 'success',
            message: 'Successful',
          });
          this.setState({ submitted: true });
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

            <div class="form-group">
              <div class="row">
                <div class="col-md-12">
                  <FileUploadInputButton
                    accept="image/jpeg,image/png,application/pdf,application/x-pdf"
                    uploadedFileName="marketplace.vendor_agreement"
                    maxSize="8000000"
                    onChange={this.handleChange}
                  />
                </div>
              </div>
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
      <div>
        {this.state.submitted
          ? <div class="col-md-8">Form submitted and is pending.</div>
          : <div class="feature-onboarding-modal">
              {this.getQuestionsElements()}

              <AsyncButton
                type="submit"
                class="btn btn-primary btn-lg"
                text="Submit"
                pendingText="Submitting..."
                onClick={handleSubmit(this.onSubmitClick)}
              />
            </div>}
      </div>
    );
  }
}
