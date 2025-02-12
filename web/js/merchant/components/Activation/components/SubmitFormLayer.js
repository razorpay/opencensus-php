import React from 'react';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Input from 'common/new-ui/Input';
import ShowWhen from 'merchant/components/ShowWhen';
import * as trackers from 'merchant/containers/Activation/ga_new';

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

  submit = (_e) => {
    if (!this.state.allowSubmit) {
      return null;
    }
    return this.props.submitActivationForm();
  };

  render() {
    return (
      <div className="SubmitForm-modal">
        <main-title>
          <Button
            className="device--mobile btn--back"
            iconBefore="arrow-back"
            onClick={this.props.closeActivationForm}
          />
          SUBMIT FORM
        </main-title>

        <div className="SubmitForm-content">
          <div className="tnc-text">
            {/* Confirmation checkbox*/}
            <Input.Check
              checked={this.state.allowSubmit}
              autoRender={true}
              disabled={this.props.isFormLocked}
              onChange={(e) => {
                const checked = e.target.checked;
                if (checked && this.props.isSyncBankVerificationEnabled) {
                  this.props.fetchMerchantData().then((res) => {
                    if (res?.data) {
                      if (this.props.isBankVerificationFailed) {
                        this.props.tracking.trackEvent(
                          window.rzpQ.onbr().failed('kyc.karza_bank_verification', {
                            bvs_attempt_count: this.props.bvsApiCount,
                          }),
                        );
                        this.props.trackEventsAction({
                          objectName: 'Insync Karza Bank Verification',
                          actionName: 'Failed',
                          screen: 'submit screen',
                          properties: {
                            bvs_attempt_count: this.props.bvsApiCount,
                          },
                          toLumberjack: false,
                        });
                      } else if (res.data?.bank_details_verification_status === 'verified') {
                        this.props.tracking.trackEvent(
                          window.rzpQ.onbr().success('kyc.karza_bank_verification', {
                            bvs_attempt_count: this.props.bvsApiCount,
                          }),
                        );
                        this.props.trackEventsAction({
                          objectName: 'Insync Karza Bank Verification',
                          actionName: 'success',
                          screen: 'submit screen',
                          properties: {
                            bvs_attempt_count: this.props.bvsApiCount,
                          },
                          toLumberjack: false,
                        });
                      }
                      this.setState({ allowSubmit: checked });
                    }
                  });
                } else {
                  this.setState({ allowSubmit: checked });
                }

                // Track session for submitting form activity (non-LA account)
                if (!this.props.isLinkedAccount && typeof window.hj === 'function') {
                  window.hj('tagRecording', ['activation_form_submitted']);
                }
              }}
            />

            {/* Primary copy */}
            <p>
              I have read and understood the{' '}
              <ShowWhen
                additionalCondition={(user) => user.isOrgAllowedFunctionality('external_links')}
              >
                <a
                  href="https://razorpay.com/terms/"
                  target="_blank"
                  className="highlight"
                  onClick={() => trackers && trackers.trackLinkClick('Terms of use')}
                  rel="noreferrer noopener"
                >
                  Terms of Service
                </a>
              </ShowWhen>
              <ShowWhen
                additionalCondition={(user) => !user.isOrgAllowedFunctionality('external_links')}
              >
                <span className="highlight">Terms of Service</span>
              </ShowWhen>{' '}
              and the{' '}
              <ShowWhen
                additionalCondition={(user) => user.isOrgAllowedFunctionality('external_links')}
              >
                <a
                  href="https://razorpay.com/privacy/"
                  target="_blank"
                  className="highlight"
                  onClick={() => trackers && trackers.trackLinkClick('Privacy Policy')}
                  rel="noreferrer noopener"
                >
                  Privacy Policy
                </a>
              </ShowWhen>
              <ShowWhen
                additionalCondition={(user) => !user.isOrgAllowedFunctionality('external_links')}
              >
                <span className="highlight">Privacy Policy</span>
              </ShowWhen>
              . By submitting the form, I agree to abide by the rules at all times.
            </p>
          </div>

          {/* Secondary copy */}
          {this.props.isBankVerificationFailed ? (
            <p className="text-error">
              {this.props.bvsApiCount == 10
                ? 'You have reached maximum limit to changed the bank account details'
                : 'Your bank details need to be reviewed again. Please check Bank Account tab and enter correct details'}
              .
            </p>
          ) : (
            <p className="text-fade">
              Please review the form before submitting. For any changes after submission, you can
              write to support
            </p>
          )}

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
