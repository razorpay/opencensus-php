import React, { Component } from 'react';
import { OtpInput } from 'merchant/components/OtpInput';
import { connect } from 'react-redux';
import ajax from 'merchant/utils/ajax';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import {
  fetchLoanApplicationMeta,
  saveD2cReportDetails,
  submitOtp,
} from 'merchant/reducers/capital';
import { triggerHotjarRecording } from 'common/utils/hotjar';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { FormLoader } from '../../components/FormSectionLoadingSkeleton';
import { Modal, ModalMask } from 'common/new-ui/Modal';
import { HOTJAR_TRIGGERS, APPLICATION_STATES } from '../constants';

@connect(
  (state) => ({
    merchantDetails: state.session.user,
    loanApplicationDetails: state.loanApplicationDetails,
  }),
  {
    submitOtp,
    saveD2cReportDetails,
    fetchLoanApplicationMeta,
    ...NotificationsActions,
  },
)
class MobileVerification extends Component {
  constructor(props) {
    super(props);
    this.state = {
      otp: '',
      hasError: false,
      generatingToken: false,
      otpSubmissionError: {},
    };
    this.token = '';
  }

  handleChange = (otp) => {
    this.setState({
      otp,
    });
  };

  componentDidMount() {
    const { configuration } = this.props.loanApplicationDetails.meta;
    this.sendReqForOtp();
    triggerHotjarRecording(HOTJAR_TRIGGERS.LOANS_CREDIT_INQUIRY);
    this.props._trackNavigationActions(
      'NEXT',
      APPLICATION_STATES.CREDIT_PULL_PENDING,
      configuration.getApplicationStateDescriptions()[APPLICATION_STATES.CREDIT_PULL_PENDING].stages
        .OTP_SCREEN,
    );
  }

  sendReqForOtp = async () => {
    this.setState({
      generatingToken: true,
    });

    const {
      loanApplicationDetails: {
        promoter_details: { data: { applicant: { id: applicantId, phones } } = {} } = {},
      } = {},
      merchantDetails: { id: merchantId, user },
    } = this.props;
    const payload = {
      applicant_id: applicantId,
      merchant_id: merchantId,
      user_id: user.id,
    };
    const mobile = phones && phones.length ? phones[0].phone_number : null;
    let sendOtpResponse;

    try {
      sendOtpResponse = await ajax(
        {
          url: 'los/service/twirp/rzp.capital.los.origination.d2c.v1.D2CBureauAPI/SendOtp',
          method: 'post',
          data: payload,
          mode: 'live',
        },
        {},
        '/merchant/api',
      );
    } catch (e) {
      this.props.showNotification({
        type: 'error',
        message: `A problem occurred while generating OTP. Please try again later.`,
      });
    }

    if (sendOtpResponse) {
      this.props.showNotification({
        type: 'success',
        message: `OTP sent ${mobile ? `to ${mobile} ` : ''}successfully.`,
      });
    }

    this.setState({
      generatingToken: false,
    });
  };

  handleSubmit = async () => {
    const { loanApplicationDetails } = this.props;
    // const { promoter_details } = loanApplicationDetails;

    // const { applicant } = promoter_details.data;

    const payload = {
      application_id: loanApplicationDetails.meta.data.application.id,
      applicant_id: loanApplicationDetails.promoter_details.data.applicant.id,
      otp: this.state.otp,
      merchant_id: this.props.merchantDetails.id,
      user_id: this.props.merchantDetails.user.id,
    };
    this.setState({
      hasError: false,
      otpSubmissionError: {},
    });
    try {
      const otpResponse = await this.props.submitOtp(payload);
      if (otpResponse && otpResponse.data) {
        this.props.saveD2cReportDetails(otpResponse);
        // this.props.navigation.next();
        this.props.fetchLoanApplicationMeta();
      } else {
        this.setState({
          hasError: true,
          otpSubmissionError: otpResponse.errors,
        });
      }
    } catch (e) {
      this.setState({
        hasError: true,
        otpSubmissionError: e,
      });
    }
  };

  goBack = (isModifyPhoneNumberCta = false) => {
    this.props._trackNavigationActions('BACK', 'PROMOTER_INFO_PENDING');
    if (isModifyPhoneNumberCta) {
      this.props._trackEvent({
        eventAction: 'Modify Phone Number',
      });
    }
    this.props.navigation.back();
  };

  getErrorMessage = () => {
    const { otpSubmissionError } = this.state;

    if (otpSubmissionError.errors && otpSubmissionError.errors.length > 0) {
      if (otpSubmissionError.status_code === 500) {
        return null;
      }
      return otpSubmissionError.errors[0];
    } else {
      return 'Something went wrong. Please reach out to support team.';
    }
  };

  getGenericErrorModal = () => {
    return (
      <ModalMask>
        <Modal
          class="credit-pull-otp-error"
          onClose={() => {
            this.setState({
              hasError: false,
              otpSubmissionError: {},
            });
          }}
        >
          <div className={`modal-header`}>
            <h3 className="modal-title">
              <img src="/dist/css/assets/capital/otp_error.svg" alt="Loading icon" />
              Something went Wrong!
            </h3>
          </div>
          <div className="modal-body">
            Sorry, we're facing connectivity issues. Please try after sometimes.
            <div class="Modal__actions">
              <AsyncBtn.Primary
                disabled={this.state.otp.trim().length < 4}
                class="m-l full-width no-margin"
                onClick={this.handleSubmit}
              >
                Try Again
              </AsyncBtn.Primary>
            </div>
          </div>
        </Modal>
      </ModalMask>
    );
  };

  render() {
    const { loanApplicationDetails } = this.props;
    return (
      <div class="creditpull-verification-container">
        {this.state.generatingToken ? (
          <FormLoader />
        ) : (
          <div className="creditpull-otp-wrapper">
            <div className="otp-label-wrapper">
              <p className="enter-otp-text">ENTER OTP</p>
              <p className="otp-helper-text">Timeout in 15:00 Min</p>
            </div>
            <div className="otp-input-container">
              {this.state.hasError && !this.getErrorMessage() && this.getGenericErrorModal()}
              <OtpInput
                onChange={this.handleChange}
                onComplete={() => {}}
                wrong={this.state.hasError}
                wrongOtpText=""
              />
              <div className="otp-helper-text-wrapper">
                {this.state.hasError && this.getErrorMessage() && (
                  <p className="otp-helper-text error-description">◦ {this.getErrorMessage()}</p>
                )}
                <p className="otp-helper-text">
                  ◦ OTP is sent to{' '}
                  {loanApplicationDetails.promoter_details.data.applicant.phones[0].phone_number}
                  <a
                    className="text-primary m-l"
                    target="_blank"
                    rel="noreferrer noopener"
                    onClick={() => this.goBack(true)}
                  >
                    Change Number
                  </a>
                </p>
                <p className="otp-helper-text">
                  ◦ Didn’t receive an OTP?
                  <a
                    className="text-primary m-l"
                    target="_blank"
                    rel="noreferrer noopener"
                    onClick={this.sendReqForOtp}
                  >
                    Resend
                  </a>
                </p>
              </div>
            </div>
          </div>
        )}
        <div className="loan-application-form-footer m-l m-r pull-right">
          <Button.Transparent onClick={this.goBack}>
            <i class="i i-chevron-left" />
            Back
          </Button.Transparent>
          <AsyncBtn.Primary
            disabled={this.state.otp.trim().length < 4}
            class="m-l"
            onClick={this.handleSubmit}
          >
            Submit
          </AsyncBtn.Primary>
        </div>
      </div>
    );
  }
}

export default MobileVerification;
