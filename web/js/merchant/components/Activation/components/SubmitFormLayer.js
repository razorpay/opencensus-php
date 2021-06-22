import React from 'react';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import ShowWhen from 'merchant/components/ShowWhen';
import { Link } from 'react-router-dom';

/*
 * Submit Form opens with backdrop inside Activation form's main content
 * - The activeTab keeps showing in the background
 * - @props
 *     {Function} submitActivationForm, call the submit form api
 * */
class SubmitFormLayer extends React.Component {
  state = {
    allowSubmit: false, // Check if checkbox is ticked
  };

  submit = e => {
    if (!this.state.allowSubmit) {
      return;
    }
    return this.props.submitActivationForm();
  };

  render() {
    return (
      <div className="SubmitForm-modal">
        <main-title>
          <Button
            class="device--mobile btn--back"
            iconBefore="arrow-back"
            onClick={this.props.closeActivationForm}
          />
          SUBMIT FORM
        </main-title>

        <div className="SubmitForm-content">
          <div className="tnc-text">
            {/* Confirmation checkbox*/}
            <Input.Check
              disabled={this.props.isFormLocked}
              onChange={e => {
                this.setState({
                  allowSubmit: e.target.checked,
                });

                // Track session for submitting form activity (non-LA account)
                if (
                  !this.props.isLinkedAccount &&
                  typeof window.hj === 'function'
                ) {
                  window.hj('tagRecording', ['activation_form_submitted']);
                }
              }}
            />

            {/* Primary copy */}
            <p>
              I have read and understood the{' '}
              <ShowWhen
                additionalCondition={user =>
                  user.isOrgAllowedFunctionality('external_links')
                }
              >
                <a
                  href="https://razorpay.com/terms/"
                  target="_blank"
                  className="highlight"
                  onClick={() =>
                    onAction && onAction.trackLinkClick('Terms of use')
                  }
                >
                  Terms & Conditions
                </a>
              </ShowWhen>
              <ShowWhen
                additionalCondition={user =>
                  !user.isOrgAllowedFunctionality('external_links')
                }
              >
                <span className="highlight">Terms & Conditions</span>
              </ShowWhen>
              ,{' '}
              <ShowWhen
                additionalCondition={user =>
                  user.isOrgAllowedFunctionality('external_links')
                }
              >
                <a
                  href="https://razorpay.com/agreement/"
                  target="_blank"
                  className="highlight"
                  onClick={() =>
                    onAction && onAction.trackLinkClick('Merchant Agreement')
                  }
                >
                  Merchant Agreement
                </a>
              </ShowWhen>
              <ShowWhen
                additionalCondition={user =>
                  !user.isOrgAllowedFunctionality('external_links')
                }
              >
                <span className="highlight">Merchant Agreement</span>
              </ShowWhen>{' '}
              and the{' '}
              <ShowWhen
                additionalCondition={user =>
                  user.isOrgAllowedFunctionality('external_links')
                }
              >
                <a
                  href="https://razorpay.com/privacy/"
                  target="_blank"
                  className="highlight"
                  onClick={() =>
                    onAction && onAction.trackLinkClick('Privacy Policy')
                  }
                >
                  Privacy Policy
                </a>
              </ShowWhen>
              <ShowWhen
                additionalCondition={user =>
                  !user.isOrgAllowedFunctionality('external_links')
                }
              >
                <span className="highlight">Privacy Policy</span>
              </ShowWhen>
              . By submitting the form, I agree to abide by the rules at all
              times.
            </p>
          </div>

          {/* Secondary copy */}
          <p className="text-fade">
            Please review the form before submitting. For any changes after
            submission, you can write to support
          </p>

          {/* Action button */}
          <AsyncBtn.Primary
            disabled={!this.state.allowSubmit}
            onClick={this.submit}
            pendingState="Submitting..."
          >
            Submit Form
          </AsyncBtn.Primary>
        </div>
      </div>
    );
  }
}

export default SubmitFormLayer;
