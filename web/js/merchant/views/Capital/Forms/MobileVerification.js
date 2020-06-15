import React, { Component } from 'react';
import { OtpInput } from 'merchant/components/OtpInput';
import { connect } from 'react-redux';
import ajax from 'merchant/utils/ajax';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import {
  changeActiveState,
  saveD2cReportDetails,
  submitOtp,
} from 'merchant/reducers/capital';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { FormLoader } from '../components/FormSectionLoadingSkeleton';

@connect(
  state => ({
    merchantDetails: state.session.user,
    loanApplicationDetails: state.loanApplicationDetails,
  }),
  {
    submitOtp,
    saveD2cReportDetails,
    changeActiveState,
    ...NotificationsActions,
  }
)
class MobileVerification extends Component {
  constructor(props) {
    super(props);
    this.state = {
      otp: '',
      hasError: false,
      generatingToken: false,
    };
    this.token = '';
  }

  handleChange = otp => {
    this.setState({
      otp,
    });
  };

  componentDidMount() {
    this.sendReqForOtp();
  }

  sendReqForOtp = async () => {
    this.setState({
      generatingToken: true,
    });
    const { loanApplicationDetails } = this.props;
    const mobile =
      loanApplicationDetails.promoter_details.data.applicant.phones[0]
        .phone_number;
    const payload = {
      medium: 'sms',
      action: 'bureau_verify',
    };
    payload['contact_mobile'] = parseInt(mobile);
    let tokenResponse;
    try {
      tokenResponse = await ajax(
        {
          url: 'otp/send',
          method: 'post',
          data: payload,
        },
        {},
        '/merchant/api'
      );
    } catch (e) {
      this.props.showNotification({
        type: 'error',
        message: `A problem occurred while generating OTP. Please try again later.`,
      });
    }
    if (tokenResponse) {
      this.token = tokenResponse.data.token;
      this.props.showNotification({
        type: 'success',
        message: `OTP sent to ${mobile} successfully.`,
      });
    }
    this.setState({
      generatingToken: false,
    });
  };

  handleSubmit = async () => {
    const { loanApplicationDetails } = this.props;
    const { promoter_details } = loanApplicationDetails;

    const { applicant } = promoter_details.data;

    const payload = {
      application_id: loanApplicationDetails.meta.data.application.id,
      applicant_id: loanApplicationDetails.promoter_details.data.applicant.id,
      token: this.token,
      otp: this.state.otp,
      merchant_id: this.props.merchantDetails.id,
      user_id: this.props.merchantDetails.user.id,
      // d2c_bureau_detail: {
      //   first_name: applicant.kyc.first_name,
      //   last_name: applicant.kyc.second_name,
      //   date_of_birth: applicant.kyc.date_of_birth,
      //   contact_mobile: applicant.phones[0].phone_number,
      //   email: applicant.emails[0].email_id,
      //   address: applicant.addresses[0].address_line1,
      //   city: applicant.addresses[0].city,
      //   state: applicant.addresses[0].state,
      //   pincode: applicant.addresses[0].pincode,
      //   pan: applicant.kyc.pan_number,
      // }
    };
    try {
      const otpResponse = await this.props.submitOtp(payload);
      if (otpResponse && otpResponse.data) {
        this.props.saveD2cReportDetails(otpResponse);
      } else {
        //check for any specific code
        this.setState({
          hasError: true,
        });
      }
    } catch (e) {
      this.setState({
        hasError: true,
      });
    }
  };

  goBack = () => {
    this.props.changeActiveState('PROMOTER_INFO_PENDING');
  };

  render() {
    const { loanApplicationDetails } = this.props;
    console.log('session', this.props.session);
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
              <OtpInput
                //throws an error if :onChange is not passed.
                onChange={this.handleChange}
                onComplete={() => {}}
                wrong={this.state.hasError}
              />
              <div className="otp-helper-text-wrapper">
                <p className="otp-helper-text">
                  ◦ OTP is sent to{' '}
                  {
                    loanApplicationDetails.promoter_details.data.applicant
                      .phones[0].phone_number
                  }
                  <a
                    className="text-primary m-l"
                    target="_blank"
                    //TODO: implement this
                    onClick={this.goBack}
                  >
                    Change Number
                  </a>
                </p>
                <p className="otp-helper-text">
                  ◦ Didn’t receive an OTP?
                  <a
                    className="text-primary m-l"
                    target="_blank"
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
          <AsyncBtn.Primary class="m-l" onClick={this.handleSubmit}>
            Submit
          </AsyncBtn.Primary>
        </div>
      </div>
    );
  }
}

export default MobileVerification;
