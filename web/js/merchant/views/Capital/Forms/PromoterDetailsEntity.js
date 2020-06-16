import React, { Component } from 'react';
import { connect } from 'react-redux';
import Input from 'common/new-ui/Input';
import {
  saveApplicantDetails,
  saveBusinessDetails,
  saveApplicationDetails,
  changeActiveState,
} from 'merchant/reducers/capital';
import Form from 'common/new-ui/Form';
import { AsyncBtn } from 'common/new-ui/Button';
import Datetime from 'react-datetime';
import { states } from 'merchant/helpers/data';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { isPreceedingState } from '../utils';
import { APPLICATION_STATES, GENDER_MAP } from '../constants';

const OutlineLockIcon = <i class="i i-outline-lock" />;

@connect(
  state => {
    return {
      session: state.session,
      loanApplicationDetails: state.loanApplicationDetails,
    };
  },
  {
    saveApplicantDetails,
    saveBusinessDetails,
    saveApplicationDetails,
    changeActiveState,
    ...NotificationsActions,
  }
)
class PromoterDetailsEntity extends Component {
  constructor(props) {
    super(props);
    this.state = {
      initialValues: {},
      formData: {},
    };
  }

  componentWillMount() {
    const { loanApplicationDetails } = this.props;
    if (
      loanApplicationDetails.business_details.data &&
      loanApplicationDetails.business_details.data.business &&
      loanApplicationDetails.business_details.data.applicant_ids &&
      loanApplicationDetails.promoter_details.data.applicant
    ) {
      const {
        addresses,
        phones,
        emails,
        kyc,
      } = loanApplicationDetails.promoter_details.data.applicant;
      const { address_line1, city, state, pincode } = addresses[0];
      const { phone_number: contact_number } = phones[0];
      const { email_id: contact_email } = emails[0];
      const {
        first_name,
        second_name,
        gender,
        date_of_birth,
        pan_number,
      } = kyc;
      this.setState({
        initialValues: {
          first_name,
          second_name,
          contact_number,
          contact_email,
          pan_number,
          gender: gender ? GENDER_MAP[gender].toString() : '0',
          date_of_birth,
          pincode,
          address: address_line1,
          city,
          state,
        },
      });
    } else {
      const { contact_email, promoter_pan } = this.props.session.user;
      this.setState({
        initialValues: {
          contact_email,
          pan_number: promoter_pan,
          gender: '0',
          date_of_birth: null,
        },
      });
    }
  }

  handleChange = ({ target }) => {
    let fieldValue = target.value;
    const fieldName = target.name;
    this.setState(prevState => ({
      formData: {
        ...prevState.formData,
        [fieldName]: fieldValue,
      },
      dirty: true,
    }));
  };

  canModify = () =>
    isPreceedingState(
      this.props.loanApplicationDetails.meta.data.application.status,
      APPLICATION_STATES.PREVERIFICATION_UPLOAD_PENDING
    );

  handleSubmit = async () => {
    const { formData, initialValues, dirty } = this.state;

    const { loanApplicationDetails, session } = this.props;

    if (this.canModify()) {
      const data = {
        ...initialValues,
        ...formData,
      };

      const {
        first_name,
        second_name,
        contact_number,
        date_of_birth,
        pincode,
        address,
        gender,
        city,
        state,
      } = data;

      const { contact_email, promoter_pan } = session.user;

      const applicantExists = Boolean(
        loanApplicationDetails.promoter_details.data.applicant &&
          loanApplicationDetails.promoter_details.data.applicant.id
      );

      const businessExists = Boolean(
        loanApplicationDetails.business_details.data.business &&
          loanApplicationDetails.business_details.data.business.id
      );

      const applicantDetails =
        loanApplicationDetails.promoter_details.data.applicant;

      const payload = {
        applicant: {
          ...(applicantExists
            ? {
                id: applicantDetails.id,
              }
            : {}),
          addresses: [
            {
              ...(applicantExists
                ? {
                    id: applicantDetails.addresses[0].id,
                  }
                : {}),
              address_type: 'ADDRESS_TYPE_RESIDENTIAL',
              address_line1: address,
              address_line2: null,
              city,
              state,
              pincode,
              country: 'India',
              is_primary: true,
            },
          ],
          phones: [
            {
              ...(applicantExists
                ? {
                    id: applicantDetails.phones[0].id,
                  }
                : {}),
              country_code: '+91',
              phone_number: contact_number,
              is_primary: true,
              // As we are giving an option to modify contact number.
              // we are unaware of its authenticity. So, record it false for now.
              // This will become true, when mobile number is verified
              // through credit pull.
              verified: false,
            },
          ],
          emails: [
            {
              ...(applicantExists
                ? {
                    id: applicantDetails.emails[0].id,
                  }
                : {}),
              email_id: contact_email,
              is_primary: true,
              verified: true,
            },
          ],
          kyc: {
            ...(applicantExists
              ? {
                  kyc_id: applicantDetails.kyc.kyc_id,
                }
              : {}),
            first_name,
            second_name,
            gender: [
              'GENDER_TYPE_MALE',
              'GENDER_TYPE_FEMALE',
              'GENDER_TYPE_OTHER',
            ][parseInt(gender)],
            date_of_birth: moment(date_of_birth).format('YYYY-MM-DD'),
            pan_number: promoter_pan,
          },
        },
      };
      if (
        this.props.loanApplicationDetails.meta.data.application.id === 'new'
      ) {
        let businessDetails;
        if (!businessExists) {
          try {
            const response = await this.props.saveBusinessDetails(
              this.props.loanApplicationDetails.business_details.data
            );
            businessDetails = response.data;
          } catch (e) {
            this.props.showNotification({
              type: 'error',
              message: e.errors ? e.errors[0] : 'Something went wrong.',
            });
          }
        } else {
          businessDetails = loanApplicationDetails.business_details.data;
        }

        let applicantResponse;
        if (businessDetails) {
          if (!applicantExists) {
            try {
              applicantResponse = await this.props.saveApplicantDetails({
                business_id: businessDetails.business.id,
                ...payload,
              });
              this.setState({
                dirty: false,
              });
            } catch (e) {
              //TODO:show appropriate errors
              console.error(e);
              this.props.showNotification({
                type: 'error',
                message: e.errors ? e.errors[0] : 'Something went wrong.',
              });
            }
          } else {
            if (dirty) {
              try {
                applicantResponse = await this.props.saveApplicantDetails({
                  business_id: [businessDetails.business.id],
                  ...payload,
                });
                this.setState({
                  dirty: false,
                });
              } catch (e) {
                console.error(e);
                this.props.showNotification({
                  type: 'error',
                  message: e.errors ? e.errors[0] : 'Something went wrong.',
                });
              }
            } else {
              applicantResponse =
                loanApplicationDetails.promoter_details.data.applicant;
            }
          }
        }

        let applicationResponse;
        if (applicantResponse) {
          try {
            const {
              loan_attributes: loanAttributes,
              products,
            } = this.props.loanApplicationDetails;
            const applicationPayload = {
              owner_id: businessDetails.business.id,
              owner_type: 'BUSINESS',
              product_id: products.data[0].id,
              requested_product_attributes: {
                amount: loanAttributes.amount,
                currency: 'INR',
                interest_rate: 10,
                tenure: loanAttributes.expected_tenure,
                credit_request_purpose: loanAttributes.credit_request_purpose,
                monthly_volume: loanAttributes.monthly_volume,
              },
              tnc_consent_attributes: {
                consent_given: true,
                given_at: Date.now(),
              },
            };

            applicationResponse = await this.props.saveApplicationDetails(
              applicationPayload
            );
            this.props.changeActiveState(
              APPLICATION_STATES.CREDIT_PULL_PENDING
            );
          } catch (e) {
            //TODO:show appropriate errors
            this.props.showNotification({
              type: 'error',
              message: e.errors ? e.errors[0] : 'Something went wrong.',
            });
          }
        }

        //TODO: Remove this after BE change.
        if (applicationResponse) {
          try {
            this.props.saveApplicationDetails({
              id: applicationResponse.data.application.id,
            });
          } catch (e) {
            //TODO:show appropriate errors
            this.props.showNotification({
              type: 'error',
              message: e.errors ? e.errors[0] : 'Something went wrong.',
            });
          }
        }
      } else {
        let applicantResponse;
        try {
          applicantResponse = await this.props.saveApplicantDetails({
            business_id: [
              loanApplicationDetails.business_details.data.business.id,
            ],
            ...payload,
          });
          this.props.changeActiveState(APPLICATION_STATES.CREDIT_PULL_PENDING);
        } catch (e) {
          //TODO:show appropriate errors
          this.props.showNotification({
            type: 'error',
            message: 'Something went wrong',
          });
        }
        if (applicantResponse) {
          this.setState({
            dirty: false,
          });
        }
      }
    } else {
      this.props.changeActiveState(APPLICATION_STATES.CREDIT_PULL_PENDING);
    }
  };

  render() {
    const { initialValues, formData } = this.state;

    const canModify = this.canModify();

    return (
      <Form
        layout="tabular"
        class="Form Form--tabular loan-application-form"
        onChange={this.handleChange}
      >
        <Input.Group label="Name" className="InputGroup--inline" required>
          <div className="Input-content">
            <Input
              key="first_name"
              defaultValue={initialValues['first_name']}
              name="first_name"
              placeholder="First Name"
              autoFocus={true}
              disabled={!canModify}
              required
              size="small"
            />
            <Input
              key="second_name"
              defaultValue={initialValues['second_name']}
              name="second_name"
              placeholder="Second Name"
              disabled={!canModify}
              required
              size="small"
            />
          </div>
        </Input.Group>
        <Input.Group
          label="Contact Details"
          className="InputGroup--inline"
          required
        >
          <div className="Input-content">
            <Input
              addonBefore={<span>+91</span>}
              key="contact_number"
              name="contact_number"
              disabled={!canModify}
              defaultValue={initialValues['contact_number']}
              required
            />
            <Input
              key="contact_email"
              defaultValue={initialValues['contact_email']}
              addonAfter={OutlineLockIcon}
              disabled={true}
              size="small"
              name="contact_email"
              required
            />
            <div className="Input-desc">
              ◦ We will send a SMS with OTP for phone number verification.
            </div>
          </div>
        </Input.Group>
        <Input
          key="pan_number"
          defaultValue={initialValues['pan_number']}
          label="PAN Number"
          size="small"
          name="pan_number"
          addonAfter={OutlineLockIcon}
          disabled={true}
          required
        />
        <Input.Radio
          key="gender"
          defaultValue={initialValues['gender']}
          label="Gender"
          name="gender"
          options={['Male', 'Female', 'Others']}
          required
          disabled={!canModify}
        />
        <Input.Group
          label="Date of Birth"
          className="InputGroup--inline"
          required
        >
          <div className="Input-content">
            <Datetime
              onChange={value =>
                this.handleChange({
                  target: {
                    name: 'date_of_birth',
                    value,
                  },
                })
              }
              name="date_of_birth"
              value={
                formData['date_of_birth'] || initialValues['date_of_birth']
              }
              dateFormat="YYYY-MM-DD"
              closeOnSelect={true}
              allowToday={false}
              allowAllDates={false}
              disablePastDates={false}
              viewMode="years"
              // disableFutureDates
              size="small"
              timeFormat={false}
              placement="topLeft"
              inputProps={{
                placeholder: 'Select a date',
                disabled: !canModify,
              }}
              required
              isInline
            />
          </div>
        </Input.Group>
        <Input
          key="pincode"
          defaultValue={initialValues['pincode']}
          label="PinCode"
          name="pincode"
          placeholder="pincode"
          required
          size="small"
          disabled={!canModify}
        />
        <Input.Textarea
          placeholder="address"
          key="address"
          defaultValue={initialValues['address']}
          label="Residential Address"
          name="address"
          size="large"
          required
          disabled={!canModify}
        />
        <Input.Group className="InputGroup--inline" required>
          <div className="Input-content">
            <Input
              key="city"
              defaultValue={initialValues['city']}
              placeholder="city"
              name="city"
              required
              disabled={!canModify}
            />
            <Input.Select
              key="state"
              defaultValue={initialValues['state']}
              size="small"
              placeholder="state"
              name="state"
              disabled={!canModify}
              options={Object.entries(states).map(([stateCode, label]) => ({
                name: stateCode,
                label,
              }))}
              required
            />
            <div style={{ margin: '12px 0' }}>
              By submitting this form you agree to our{' '}
              <a
                className="text-primary"
                target="_blank"
                href="https://razorpay.com/terms/"
              >
                T&C
              </a>
              and our
              <a
                className="text-primary"
                target="_blank"
                href="https://razorpay.com/terms/"
              >
                Privacy Policy
              </a>
            </div>
          </div>
        </Input.Group>
        <div class="loan-application-form-footer">
          <AsyncBtn.Primary
            type="submit"
            class="btn btn-primary pull-right no-margin"
            onClick={this.handleSubmit}
          >
            {canModify ? 'Save & Next' : 'Next'}
            <i className="i i-chevron-right" />
          </AsyncBtn.Primary>
          <button
            className="btn btn-link pull-right"
            onClick={() =>
              this.props.changeActiveState('BUSINESS_INFO_PENDING')
            }
          >
            <i className="i i-chevron-left" />
            Back
          </button>
        </div>
      </Form>
    );
  }
}

export default PromoterDetailsEntity;
