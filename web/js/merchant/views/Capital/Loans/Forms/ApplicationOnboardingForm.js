import React, { Component } from 'react';
import Datetime from 'react-datetime';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import { states } from 'merchant/helpers/data';
import { connect } from 'react-redux';
import { isMobile, isValidPinCode, pinCode } from 'common/utils/validators';
import { AsyncBtn } from 'common/new-ui/Button';
import {
  saveApplicantDetails,
  saveApplicationDetails,
  saveBusinessDetails,
  getBusinessByMerchantId,
  fetchApplicantDetails,
} from 'merchant/reducers/capital';
import { BUSINESS_TYPES, GENDER_OPTIONS } from '../constants';

const personalInfoSelector = (user) => ({
  first_name: user.contact_name,
  second_name: '',
  date_of_birth: '',
  contact_number: user.contact_mobile,
  pincode: user.business_registered_pin,
  address: `${user.business_registered_address || ''} ${user.business_registered_address_l2 || ''}`,
  city: user.business_registered_city,
  state: user.business_registered_state,
  gender: GENDER_OPTIONS[0].name,
});

const validateMobileNumber = (input) => {
  if (!isMobile(input)) {
    return 'Please enter valid mobile number';
  } else {
    return '';
  }
};

const validatePinCode = (input) => {
  if (!isValidPinCode(input)) {
    return 'Please enter valid pincode';
  } else {
    return '';
  }
};

@connect(
  (state) => {
    return {
      initialValues: personalInfoSelector(state.session.user),
      user: state.session.user,
      loanApplicationDetails: state.loanApplicationDetails,
    };
  },
  {
    saveBusinessDetails,
    saveApplicationDetails,
    getBusinessByMerchantId,
    fetchApplicantDetails,
    saveApplicantDetails,
  },
)
class ApplicationOnboardingForm extends Component {
  constructor() {
    super();
    this.state = {
      formData: {
        state: Object.entries(states)[0][0],
        gender: GENDER_OPTIONS[0].name,
      },
    };
  }

  componentDidMount() {
    this.fetch();
  }

  fetch = async () => {
    const businessDetails = await this.props.getBusinessByMerchantId({
      reference_id: this.props.user.current,
      reference_type: 'MID',
    });
    if (businessDetails && businessDetails.data && businessDetails.data.applicant_ids) {
      const applicantDetails = await this.props.fetchApplicantDetails({
        applicant_id: businessDetails.data.applicant_ids[0],
      });
      if (applicantDetails.data.applicant) {
        this.derivePromoterDetailsFormValues(applicantDetails.data.applicant);
      } else {
        this.setState({
          formData: this.props.initialValues,
        });
      }
    }
  };

  derivePromoterDetailsFormValues = (applicant) => {
    const formData = {
      first_name: applicant.kyc.first_name,
      second_name: applicant.kyc.second_name,
      date_of_birth: applicant.kyc.date_of_birth,
      gender: applicant.kyc.gender || GENDER_OPTIONS[0].name,
      contact_number: applicant.phones[0].phone_number,
      address: applicant.addresses[0].address_line1 + (applicant.addresses[0].address_l2 || ''),
      pincode: applicant.addresses[0].pincode,
      city: applicant.addresses[0].city,
      state: applicant.addresses[0].state || Object.entries(states)[0][0],
    };
    this.setState({
      formData,
    });
  };

  handleChange = ({ target }) => {
    let fieldValue = target.value;
    const fieldName = target.name;
    this.setState((prevState) => ({
      formData: {
        ...prevState.formData,
        [fieldName]: fieldValue,
      },
      dirty: true,
    }));
  };

  isValidDate = (current) => {
    const age = moment().diff(current, 'years');
    return age < 100 && age > 18;
  };

  isValidForm = () => {
    const mandatoryFields = [
      'date_of_birth',
      'first_name',
      'second_name',
      'contact_number',
      'gender',
      'pincode',
      'address',
      'city',
      'state',
    ];
    const { formData } = this.state;

    return (
      mandatoryFields.every((field) => !!formData[field]) &&
      this.isValidDate(formData['date_of_birth']) &&
      isValidPinCode(formData.pincode) &&
      isMobile(formData.contact_number)
    );
  };

  createBusinessEntity = () => {
    const { user } = this.props;

    const payload = {
      business: {
        reference_id: user.current,
        reference_type: 'MID',
        legal_name: user.business_name,
        deed_type: BUSINESS_TYPES[parseInt(user.business_type)],
        business_pan: user.company_pan,
        addresses: [
          {
            address_type: 'ADDRESS_TYPE_BUSINESS',
            address_line1: user.business_registered_address,
            address_line2: user.business_registered_address_l2,
            city: user.business_registered_city,
            state: user.business_registered_state,
            pincode: user.business_registered_pin,
            country: user.business_registered_country || 'IN',
            is_primary: true,
          },
        ],
        phones: [
          {
            country_code: '+91',
            phone_number: user.contact_mobile,
            is_primary: true,
            is_verified: user.user.contact_mobile_verified,
          },
        ],
        emails: [
          {
            email_id: user.contact_email,
            is_primary: true,
            verified: true,
          },
        ],
      },
    };
    return this.props.saveBusinessDetails(payload);
  };

  saveBusinessDetails = () => {
    const { business_details } = this.props.loanApplicationDetails;

    if (business_details.data && business_details.data.business) {
      return Promise.resolve({
        data: business_details.data,
      });
    } else {
      return this.createBusinessEntity();
    }
  };

  saveApplicantDetails = (businessDetails) => {
    const { loanApplicationDetails, applicantPan } = this.props;

    const { contact_email } = this.props.user;

    const applicantExists = Boolean(
      loanApplicationDetails.promoter_details.data.applicant &&
        loanApplicationDetails.promoter_details.data.applicant.id,
    );
    const applicantDetails = loanApplicationDetails.promoter_details.data.applicant;

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
    } = this.state.formData;

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
          gender,
          date_of_birth: moment(date_of_birth).format('YYYY-MM-DD'),
          pan_number: applicantPan,
        },
      },
    };

    return this.props.saveApplicantDetails({
      business_id: applicantExists
        ? [businessDetails.data.business.id]
        : businessDetails.data.business.id,
      ...payload,
    });
  };

  createApplication = async (businessId) => {
    const applicationPayload = {
      owner_id: businessId,
      owner_type: 'BUSINESS',
      product_id: this.props.productId,
      requested_product_attributes: {
        currency: 'INR',
        interest_rate: 10,
      },
      tnc_consent_attributes: {
        consent_given: true,
        given_at: Date.now(),
      },
    };
    await this.props.saveApplicationDetails(applicationPayload);
  };

  handleSubmit = async () => {
    let businessDetails;
    try {
      businessDetails = await this.saveBusinessDetails();
    } catch (e) {
      // TODO:handle error
    }
    await this.saveApplicantDetails(businessDetails);
    if (businessDetails && businessDetails.data && businessDetails.data.business) {
      await this.createApplication(businessDetails.data.business.id);
    }
  };

  render() {
    const { formData } = this.state;
    const canModify = true;

    return (
      <Form onChange={this.handleChange}>
        <div class="flex">
          <Input
            key="first_name"
            label="First Name"
            defaultValue={formData['first_name']}
            name="first_name"
            placeholder="First Name"
            disabled={!canModify}
            required
            size="small"
            class="InputGroup--vTop"
          />
          <Input
            key="second_name"
            label="Last Name"
            defaultValue={formData['second_name']}
            name="second_name"
            placeholder="Second Name"
            disabled={!canModify}
            autoFocus={true}
            required
            size="small"
            class="InputGroup--vTop"
          />
        </div>
        <div className="flex">
          <Input.Group
            label="Date of Birth"
            className="Input--small InputGroup--inline InputGroup--vTop"
            required
          >
            <div className="Input-content">
              <div className="Input-elWrapper">
                <Datetime
                  onChange={(value) =>
                    this.handleChange({
                      target: {
                        name: 'date_of_birth',
                        value,
                      },
                    })
                  }
                  name="date_of_birth"
                  value={formData['date_of_birth']}
                  dateFormat="YYYY-MM-DD"
                  closeOnSelect={true}
                  isValidDate={this.isValidDate}
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
            </div>
          </Input.Group>
          <Input.Select
            key="state"
            label="Gender"
            defaultValue={formData['gender']}
            size="small"
            name="gender"
            disabled={!canModify}
            options={GENDER_OPTIONS}
            required
            class="InputGroup--vTop"
          />
        </div>
        <div>
          <div className="flex">
            <Input
              addonBefore={<span>+91</span>}
              label="Contact Number"
              key="contact_number"
              type="number"
              name="contact_number"
              disabled={!canModify}
              defaultValue={formData['contact_number']}
              required
              validator={validateMobileNumber}
              size="small"
              class="InputGroup--vTop"
            />
            <Input
              key="pincode"
              defaultValue={formData['pincode']}
              label="Pincode"
              name="pincode"
              placeholder="Pincode"
              required
              validator={validatePinCode}
              size="small"
              disabled={!canModify}
              class="InputGroup--vTop"
            />
          </div>
        </div>
        <Input.Textarea
          placeholder="Address"
          key="address"
          value={formData['address']}
          label="Residential Address"
          name="address"
          size="large"
          required
          disabled={!canModify}
          class="InputGroup--vTop"
        />
        <div class="flex">
          <Input
            key="city"
            defaultValue={formData['city']}
            placeholder="city"
            name="city"
            label="City"
            required
            disabled={!canModify}
            class="InputGroup--vTop"
            size="small"
          />
          <Input.Select
            key="state"
            defaultValue={formData['state']}
            size="small"
            placeholder="state"
            name="state"
            disabled={!canModify}
            options={Object.entries(states).map(([stateCode, label]) => ({
              name: stateCode,
              label,
            }))}
            required
            label="State"
            class="InputGroup--vTop"
          />
        </div>
        <div style={{ margin: '12px 0' }}>
          By submitting this form you agree to our{' '}
          <a
            className="text-primary"
            target="_blank"
            href="https://razorpay.com/terms/"
            onClick={() => {
              this.props._trackEvent({
                eventAction: 'Application | T&C',
                eventLabel: 'Check Loan Eligibility | Promoter Details',
              });
            }}
          >
            T&C&nbsp;
          </a>
          and our&nbsp;
          <a
            className="text-primary"
            target="_blank"
            href="https://razorpay.com/terms/"
            onClick={() => {
              this.props._trackEvent({
                eventAction: 'Application | T&C',
                eventLabel: 'Check Loan Eligibility | Promoter Details',
              });
            }}
          >
            Privacy Policy
          </a>
        </div>
        <div>
          <AsyncBtn.Primary
            type="submit"
            class="btn btn-primary no-margin"
            onClick={this.handleSubmit}
            disabled={!this.isValidForm()}
          >
            Check your Eligibility
            <i className="i i-chevron-right" />
          </AsyncBtn.Primary>
        </div>
      </Form>
    );
  }
}

export default ApplicationOnboardingForm;
