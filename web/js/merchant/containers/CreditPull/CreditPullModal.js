import React, { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import ReduxDatetime from 'rzp/ui/ReduxDatetime';
import InputField from 'rzp/ui/Forms/InputField';
import { RadioGroup } from 'rzp/ui/Forms/RadioGroup';
import ModalHeader from 'rzp/ui/ModalHeader';
import Alert from 'rzp/ui/Forms/Alert';
import { required, mobile } from 'rzp/utils/validators';
import { showNotification } from 'rzp/modules/notifications';
import { states } from 'rzp/utils/constants';
import * as MerchantActions from 'merchant/modules/b-merchants';
import * as ModalActions from 'rzp/modules/modals';
import bMerchantReducer from 'merchant/modules/b-merchants';
import CheckBoxField from 'rzp/ui/Forms/CheckboxField';
import {
  VerifyOtp,
  AskMobileNumber,
} from 'merchant/containers/Team/TwoFaModals';
import CreditPullClose from './CreditPullClose';
import CreditPullSuccess from './CreditPullSuccess';
import ajax from '../../../merchantLA/utils/ajax';

const validate = values => {
  const errors = {};
  if (!values.hasOwnProperty('gender')) {
    errors.gender = 'Please select a gender';
  }
  if (!values.hasOwnProperty('dateOfBirth')) {
    errors.dateOfBirth = 'Please select a DOB';
  }
  if (values.hasOwnProperty('pinCode')) {
    let pin = Number(values.pinCode);
    if (!pin || pin < 100000 || pin > 999999) {
      errors.pinCode = 'Please enter 6 digit pin code';
    }
  }
  return errors;
};

@connect(
  state => {
    return {
      initialValues: state.bMerchant.merchantData,
      user: state.session.user,
    };
  },
  {
    showNotification,
    ...MerchantActions,
    ...ModalActions,
    bMerchantReducer,
  }
)
@reduxForm({
  form: 'b-merchant',
  validate,
})
export default class CreditPullModal extends Component {
  constructor(props) {
    super(props);

    this.SMALL_MODAL = 'small';
    this.dateFormatType = 'YYYY-MM-DD';
    this.dateContainer = React.createRef();
    this.state = {
      errors: null,
      isLoading: false,
      hasAcceptedTerms: false,
    };
  }

  componentWillMount() {
    this.props.fetchBMerchant();
  }

  savePhone = newNumber => {
    let saveProps = { ...this.state.merchantData };
    saveProps['contact_mobile'] = newNumber;
    this.saveAndProceed(saveProps, newNumber);
  };

  save = props => {
    let saveProps = { ...props };
    if (props.date_of_birth.hasOwnProperty('_isAMomentObject')) {
      saveProps['date_of_birth'] = props.date_of_birth.format(
        this.dateFormatType
      );
    }
    this.setState({
      merchantData: saveProps,
      isLoading: true,
    });
    this.saveAndProceed(saveProps, props.contact_mobile);
  };

  isValidDate = current => {
    let yearsBefore = window.moment().subtract(18, 'years');
    return current.isBefore(yearsBefore);
  };

  saveAndProceed = (saveProps, mobile) => {
    this.props
      .saveBMerchant(saveProps)
      .then(merchant => {
        return Promise.all([this.sendReqForOtp(mobile), merchant]);
      })
      .then(
        ([{ data: { token } }, { id: merchantId, contact_mobile: mobile }]) => {
          this.openVerify(token, merchantId, mobile);
        }
      )
      .catch(error => {
        this.props.showNotification({
          type: 'error',
          message: 'Something went wrong',
          hidePrevious: true,
        });
        this.props.closeModal();
      });
  };

  verifyMobile = token => {
    this.props.openModal({
      component: (
        <AskMobileNumber
          closeModal={this.props.closeModal}
          blank={true}
          customTitle="Enter OTP Number"
          mobileValidation={true}
          customMessage="Update the phone number for OTP verification. The phone number should exist in the PAN database."
          onSubmit={data => {
            this.savePhone(data.contact_mobile);
          }}
        />
      ),
      size: this.SMALL_MODAL,
    });
  };

  openVerify = (tokenStuff, merchantId, mobile) => {
    this.props.openModal({
      component: (
        <VerifyOtp
          closeModal={this.props.closeModal}
          customClass={'credit-otp'}
          onSubmit={data => {
            return this.sendReqForOtpConfirmation(
              data,
              mobile,
              tokenStuff,
              merchantId
            )
              .then(
                ({ data: { report, score, max_loan_amount, reportId } }) => {
                  this.openReportScreen(
                    report,
                    score,
                    max_loan_amount,
                    reportId
                  );
                }
              )
              .catch(error => {
                throw error;
              });
          }}
          onResend={() => {
            return this.sendReqForOtp(mobile, tokenStuff).then(() => {
              this.props.showNotification({
                type: 'success',
                message: 'The OTP was resent',
                hidePrevious: true,
              });
            });
          }}
          onChangeMobileNumber={() => this.verifyMobile(tokenStuff, merchantId)}
          contactMobile={mobile}
        />
      ),
      size: this.SMALL_MODAL,
    });
  };

  openErrorScreen = message => {
    this.props.openModal({
      component: <CreditPullClose message={message} />,
      size: this.SMALL_MODAL,
    });
  };

  openReportScreen = (report, score, maxLoan, reportId) => {
    this.props.openModal({
      component: (
        <CreditPullSuccess
          score={score}
          report={report}
          maxLoan={maxLoan}
          reportId={reportId}
        />
      ),
      size: 'large',
    });
  };

  sendReqForOtpConfirmation = (data, mobile, token, merchantId) => {
    let payload = {
      otp: data.otp,
      token,
    };
    payload['contact_mobile'] = parseInt(mobile);
    return ajax(
      {
        url: `d2c_bureau_details/${merchantId}/otp_submit`,
        method: 'POST',
        data: payload,
      },
      {},
      '/merchant/api'
    );
  };

  initialAlign = () => {
    //Hate doing this unfortunately the library doesn't provide any other way to do this.
    if (
      this.props.initialValues &&
      !this.props.initialValues.hasOwnProperty('date_of_birth') &&
      this.dateContainer
    ) {
      this.dateContainer.current.querySelector('div .rdtPrev span').click();
      setTimeout(() => {
        this.dateContainer.current.querySelector('div .rdtPrev span').click();
        this.dateContainer = null;
      }, 50);
    }
  };

  sendReqForOtp = (mobile, token) => {
    const payload = {
      medium: 'sms',
      action: 'bureau_verify',
    };
    if (token) {
      payload['token'] = token;
    }
    payload['contact_mobile'] = parseInt(mobile);
    return ajax(
      {
        url: 'otp/send',
        method: 'post',
        data: payload,
      },
      {},
      '/merchant/api'
    );
  };

  renderPreEnablement = () => {
    const { handleSubmit } = this.props;
    return (
      <>
        <ModalHeader
          title={'Business Details'}
          onCloseClick={() => {
            this.props.closeModal();
          }}
        />
        <form className="form-horizontal bureau-merchant-form">
          <div className="modal-body">
            <Alert type="error" message={this.state.errors} />
            <div className="form-group">
              <label className="col-md-3 control-label help-label label-required">
                Name
              </label>
              <div className="col-md-4">
                <Field
                  name="id"
                  component={InputField}
                  required={true}
                  className="hidden"
                />
                <Field
                  name="first_name"
                  component={InputField}
                  class="form-control"
                  placeholder="First Name"
                  validate={required('Please enter a first name')}
                />
              </div>

              <div className="col-md-4">
                <Field
                  name="last_name"
                  component={InputField}
                  class="form-control"
                  placeholder="Last Name"
                  validate={required('Please enter a last email')}
                />
              </div>
            </div>

            <div className="form-group">
              <label className="col-md-3 control-label label-required">
                Contact Details
              </label>
              <div className="col-md-4">
                <Field
                  name="contact_mobile"
                  component={InputField}
                  class="form-control"
                  placeholder="Mobile Number"
                  validate={[
                    required('Please enter a mobile number'),
                    mobile('Please enter a valid mobile number'),
                  ]}
                />
              </div>

              <div className="col-md-4">
                <Field
                  name="email"
                  component={InputField}
                  class="form-control"
                  placeholder="Email"
                  disabled={true}
                />
              </div>
            </div>

            <div className="form-group">
              <div className="col-md-3" />
              <div className="col-md-8">
                <i className="i-warning warning" /> Please Use the Mobile No.
                Registered with your Credit Card/Loan account
              </div>
            </div>

            <div className="form-group">
              <label className="col-md-3 control-label label-required">
                PAN Number
              </label>
              <div className="col-md-4">
                <Field
                  name="pan"
                  component={InputField}
                  class="form-control"
                  placeholder="PAN Number"
                  disabled={true}
                />
              </div>
            </div>

            <div className="form-group">
              <label className="col-md-3 control-label label-required">
                Gender
              </label>
              <div className="col-md-4">
                <Field
                  component={RadioGroup}
                  name="gender"
                  required={true}
                  options={[
                    { title: 'Male', value: 'male' },
                    { title: 'Female', value: 'female' },
                  ]}
                />
              </div>
            </div>

            <div className="form-group">
              <label className="col-md-3 control-label label-required">
                Date of Birth
              </label>
              <div className="col-md-4 red-cal-date" ref={this.dateContainer}>
                <Field
                  name="date_of_birth"
                  component={ReduxDatetime}
                  placeholder="Select a date"
                  dateFormat="YYYY-MM-DD"
                  required={true}
                  viewMode={'years'}
                  timeFormat={false}
                  handleFocus={this.initialAlign}
                  isValidDate={this.isValidDate}
                />
              </div>
            </div>

            <div className="form-group">
              <label className="col-md-3 control-label label-required">
                Residential Address
              </label>

              <div className="col-md-8">
                <Field
                  name="address"
                  component={InputField}
                  tagName="textarea"
                  type="textarea"
                  class="form-control"
                  validate={required('Please enter the address line one')}
                />
              </div>
              <div className="col-md-3" />
              <div className="col-md-4 paddyTop15">
                <Field
                  name="city"
                  component={InputField}
                  class="form-control"
                  placeholder="City"
                  validate={required('Please enter a city')}
                />
              </div>

              <div className="col-md-4 paddyTop15">
                <Field
                  name="state"
                  component={InputField}
                  tagName="select"
                  class="form-control"
                  placeholder="State"
                >
                  {Object.keys(states).map(stateCode => (
                    <option value={stateCode} key={stateCode}>
                      {states[stateCode]}
                    </option>
                  ))}
                </Field>
              </div>
            </div>

            <div className="form-group">
              <label className="col-md-3 control-label label-required">
                Pin Code
              </label>
              <div className="col-md-4">
                <Field
                  name="pincode"
                  component={InputField}
                  class="form-control"
                  placeholder="Pin Code"
                />
              </div>
            </div>

            <div className="form-group">
              <div className="col-md-3" />
              <div className="col-md-8 orange-pad">
                We verify the details with the PAN database. Please ensure you
                enter the correct details.
              </div>
            </div>
          </div>

          <div className="modal-footer">
            <div className="form-group">
              <div className="col-md-3">
                <Field
                  name="consent"
                  component={CheckBoxField}
                  checked={this.state.hasAcceptedTerms}
                  onChange={this.handleCheckboxChange}
                />
              </div>
              <label htmlFor="consent" className="cap-consent col-md-9">
                I hereby agree to share my credit information with Razorpay and
                its partners. By submitting this form I hereby agree to the
                <a
                  target="_blank"
                  href="https://razorpay.com/capital/credit-report-terms"
                >
                  {' Terms & Conditions.'}
                </a>
              </label>
            </div>
            {this.state.isLoading ? (
              <div className="form-group">
                <div className="col-md-9" />
                <div className="col-md-3">
                  <div className="loader" />
                </div>
              </div>
            ) : (
              <></>
            )}
            <span className="exp-logo-text">Powered by</span>
            <img
              className="exp-logo"
              src="https://cdn.razorpay.com/static/assets/experian_logo.png"
            />

            <button
              type="button"
              className="btn btn-default"
              onClick={this.props.closeModal}
            >
              Cancel
            </button>

            <AsyncButton
              type="submit"
              class="btn btn-primary"
              text={'Verify & Submit'}
              disabled={!this.state.hasAcceptedTerms}
              onClick={handleSubmit(this.save)}
            />
          </div>
        </form>
      </>
    );
  };

  handleCheckboxChange = event => {
    this.setState({
      hasAcceptedTerms: !this.state.hasAcceptedTerms,
    });
  };

  render() {
    return (
      <div className="container-cred-pull-modal">
        {this.renderPreEnablement()}
      </div>
    );
  }
}
