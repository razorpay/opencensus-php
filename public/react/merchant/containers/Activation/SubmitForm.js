import { Component } from 'react';
import { connect } from 'react-redux';
import { Field } from 'redux-form';
import AsyncButton from 'react-async-button';
import Fieldset from 'rzp/ui/Forms/Fieldset';
import { required } from 'rzp/utils/validators';

@connect(state => state.session, null)
export default class SubmitForm extends Component {
  render() {
    let { handleSubmit, save, goBack, invalid } = this.props;
    let { locked, submitted } = this.props.data;

    return (
      <form class="form-horizontal" onSubmit={handleSubmit(save)}>
        <Fieldset disabled={locked}>
          <div class="form-group">
            <div class="col-md-offset-3 col-md-9">
              <div class="checkbox rzpCheckbox submit-form">
                <Field
                  name="agree_terms"
                  id="agree_terms"
                  component="input"
                  type="checkbox"
                  validate={value => {
                    if (!value) {
                      return 'Required';
                    }
                  }}
                />
                <label for="agree_terms">
                  <div class="submit-label">
                    I have read and understood the{' '}
                    <a
                      href="https://razorpay.com/terms/"
                      target="_blank"
                      class="highlight"
                    >
                      terms and conditions
                    </a>
                    , the{' '}
                    <a
                      href="https://razorpay.com/agreement/"
                      target="_blank"
                      class="highlight"
                    >
                      merchant agreement
                    </a>
                    , and the{' '}
                    <a
                      href="https://razorpay.com/privacy/"
                      target="_blank"
                      class="highlight"
                    >
                      privacy policy
                    </a>{' '}
                    and agree to abide by them at all times.
                  </div>
                </label>
              </div>
            </div>
          </div>

          {this.props.session.org.custom_code === 'hdfc' ? (
            <div class="form-group">
              <div class="col-md-offset-3 col-md-9">
                <strong>Note:</strong> This solution is a joint initiative
                between HDFC Bank Ltd. and Razorpay.
                <br />
                Your primary relationship will be maintained with HDFC Bank Ltd.
              </div>
            </div>
          ) : null}

          <div class="form-group">
            <div class="col-md-offset-3 col-md-9">
              <div class="btn-toolbar">
                <AsyncButton
                  type="button"
                  class="btn btn-default pull-left"
                  text="Back"
                  onClick={goBack}
                />

                <AsyncButton
                  class="btn btn-primary"
                  text="Click here to Submit"
                  pendingText="Submitting..."
                  disabled={!!submitted || invalid}
                  onClick={handleSubmit(save)}
                />
              </div>
            </div>
          </div>
        </Fieldset>
      </form>
    );
  }
}
