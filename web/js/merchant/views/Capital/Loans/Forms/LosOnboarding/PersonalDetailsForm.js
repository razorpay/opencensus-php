import React from 'react';
import { connect } from 'react-redux';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import Datetime from 'react-datetime';
import { AsyncBtn } from 'common/new-ui/Button';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';
import { states } from 'merchant/helpers/data';
import { GENDER_OPTIONS } from '../../constants';
import { merchantFetch } from 'merchant/utils/ajax';
import {
  validateAddress,
  validateEmail,
  validateFirstName,
  validateMobile,
  validatePanNumber,
  validatePinCode,
  validateLastName,
} from '../Validators';
import { trackMajorStackholderFill, trackPersonalFormTab } from '../ga';
import { getPersonalFormError } from '../Helpers/getPersonalFormErrors';
import { statesOptions } from '../Helpers/getStatesOptions';
import { isValidPinCode } from 'common/utils/validators';
import BureauCompliance from 'merchant/views/Capital/Loans/BureauCompliance';
import moment from 'moment';

const personalInfoSelector = (user) => ({
  first_name: user.contact_name,
  second_name: '',
  date_of_birth: '',
  contact_number: user.contact_mobile,
  contact_email: user.email,
  pincode: user.business_registered_pin,
  address: `${user.business_registered_address || ''} ${user.business_registered_address_l2 || ''}`,
  city: user.business_registered_city,
  state: user.business_registered_state,
  gender: GENDER_OPTIONS[0].name,
  pan_number: '',
});

const initialFormData = {
  first_name: '',
  second_name: '',
  date_of_birth: '',
  contact_number: '',
  contact_email: '',
  pincode: '',
  address: '',
  city: '',
  state: '',
  gender: GENDER_OPTIONS[0].name,
  pan_number: '',
};

export const getCityAndState = async (pincode) => {
  const result = await merchantFetch({
    url: `pincodes/${pincode}`,
  });
  return result;
};

const PersonalDetailsForm = ({
  merchantId,
  isPending,
  showNotification,
  initialFormValues,
  loanApplicationDetails,
  handlePersonalDetailsSubmit,
}) => {
  const [majorityStakeholder, setMajorityStakeholder] = React.useState(null);
  const [formData, setFormData] = React.useState(initialFormData);
  const [pincodeError, setPincodeError] = React.useState(false);
  const [fieldsChanged, setFieldsChanged] = React.useState(false);
  const getNameForConsent = `${formData.first_name} ${formData.second_name}`;

  const handleSubmit = () => {
    const errors = getPersonalFormError(formData);

    if (errors.length) {
      showNotification({
        type: 'error',
        message: errors,
      });
    } else {
      handlePersonalDetailsSubmit({
        ...formData,
        majority_stakeholder: majorityStakeholder,
      });
    }
  };

  const handleChange = ({ target }) => {
    setFieldsChanged(true);
    const { value, name } = target;
    setFormData({
      ...formData,
      [name]: value,
    });
  };

  const handleMajorityStakeholderChange = ({ target }) => {
    setFieldsChanged(false);
    const value = target.value === 'true';
    trackMajorStackholderFill(merchantId, value ? 'Yes' : 'No', 'Summary');
    setMajorityStakeholder(value);
  };

  const derivePromoterDetailsFormValues = (applicant = {}) => {
    const data = {
      first_name: applicant.kyc.first_name,
      second_name: applicant.kyc.second_name,
      date_of_birth: applicant.kyc.date_of_birth,
      gender: applicant.kyc.gender || GENDER_OPTIONS[0].name,
      contact_number: applicant?.phones?.[0]?.phone_number || '',
      contact_email: applicant?.emails?.[0]?.email_id,
      address:
        (applicant?.addresses?.[0]?.address_line1 || '') +
        (applicant?.addresses?.[0]?.address_l2 || ''),
      pincode: applicant?.addresses?.[0]?.pincode || '',
      city: applicant?.addresses?.[0]?.city || '',
      state: applicant?.addresses?.[0]?.state || Object.entries(states)[0][0],
      pan_number: applicant?.kyc?.pan_number || '',
    };
    setFormData(data);
  };

  function fetchDetails() {
    if (loanApplicationDetails.promoter_details.data.applicant) {
      derivePromoterDetailsFormValues(loanApplicationDetails.promoter_details?.data?.applicant);
    } else {
      setFormData(initialFormValues);
    }
  }

  React.useEffect(() => {
    trackPersonalFormTab(merchantId);
  }, []);

  React.useEffect(() => {
    if (majorityStakeholder) fetchDetails();
    else setFormData(initialFormData);
  }, [majorityStakeholder]);

  function isValidDate(current) {
    const age = moment().diff(current, 'years');
    return age <= 65 && age >= 18;
  }

  const loadCityAndState = async () => {
    try {
      const { data } = await getCityAndState(formData.pincode);
      setFormData({
        ...formData,
        city: data.city,
        state: data.state_code,
      });
      setPincodeError(false);
    } catch (error) {
      setPincodeError(true);
    }
  };

  React.useEffect(() => {
    if (isValidPinCode(formData.pincode)) {
      loadCityAndState();
    }
  }, [formData.pincode]);

  return (
    <div className="los-personal-details-form">
      <div className="top-header">
        <div className="question">Do you own atleast 20% of business?</div>
        <Input.Radio
          className={`Input--vTop radios ${
            majorityStakeholder !== null ? (majorityStakeholder === true ? 'yes' : 'no') : ''
          }`}
          defaultValue={majorityStakeholder}
          noDefaultSelectedValue={true}
          onChange={handleMajorityStakeholderChange}
          options={[
            {
              label: 'Yes',
              value: true,
            },
            {
              label: 'No',
              value: false,
            },
          ]}
          required
        />
      </div>
      {majorityStakeholder !== null && (
        <>
          <div className="info flex">
            <span className="info-border" />
            <div className="info-text">
              {majorityStakeholder
                ? 'Business loans requires KYC details of the key stakeholder.  Kindly click on the option No if you are not a key stakeholder.'
                : 'You hereby confirm that, you are an individual with significant responsibility for managing the business (e.g: CEO, CFO, COO, Partner, VP, Treasurer, or CA) and is applying on behalf of the key stakeholder.'}
            </div>
          </div>
          {majorityStakeholder ? (
            <h5>Please confirm the business owner details availabe with us</h5>
          ) : (
            <h5>
              Please provide the key stakeholder details with more than 20% business ownership
            </h5>
          )}
          <Form key={majorityStakeholder}>
            <div className="flex los-row">
              <Input
                label="First Name"
                value={formData.first_name}
                onChange={handleChange}
                name="first_name"
                placeholder="First Name"
                size="small"
                className="InputGroup--vTop Input--required"
                validator={fieldsChanged && validateFirstName}
              />
              <Input
                label="Last Name"
                value={formData.second_name}
                onChange={handleChange}
                name="second_name"
                placeholder="Last Name"
                size="small"
                className="InputGroup--vTop Input--required"
                validator={fieldsChanged && validateLastName}
              />
            </div>
            <div
              className={`flex los-row date-of-birth ${
                formData.date_of_birth && !isValidDate(formData.date_of_birth) ? 'error' : ''
              }`}
            >
              <Input.Group
                label="Date of Birth"
                className="Input--small InputGroup--inline InputGroup--vTop Input--required"
              >
                <div className="Input-content">
                  <div className="Input-elWrapper">
                    <Datetime
                      onChange={(value) =>
                        handleChange({
                          target: {
                            name: 'date_of_birth',
                            value,
                          },
                        })
                      }
                      name="date_of_birth"
                      value={formData.date_of_birth}
                      dateFormat="YYYY-MM-DD"
                      closeOnSelect={true}
                      isValidDate={isValidDate}
                      allowToday={false}
                      allowAllDates={false}
                      disablePastDates={false}
                      viewMode="years"
                      disableFutureDates
                      size="small"
                      timeFormat={false}
                      placement="topLeft"
                      inputProps={{
                        placeholder: 'Select a date',
                      }}
                      isInline
                    />
                    {formData.date_of_birth && !isValidDate(formData.date_of_birth) && (
                      <div class="Input-error d-block">
                        To apply, you must be between 18 to 65 years
                      </div>
                    )}
                  </div>
                </div>
              </Input.Group>
              <Input.Select
                label="Gender"
                value={formData.gender}
                onChange={handleChange}
                size="small"
                name="gender"
                options={GENDER_OPTIONS}
                className="InputGroup--vTop Input--required"
              />
            </div>
            <div className="flex los-row">
              <div className="contact-number">
                <Input
                  addonBefore={<span>+91</span>}
                  label="Contact Number"
                  type="number"
                  name="contact_number"
                  value={formData.contact_number}
                  onChange={handleChange}
                  validator={fieldsChanged && validateMobile}
                  placeholder="Contact Number"
                  size="small"
                  key="contact_number"
                  className="InputGroup--vTop Input--required"
                />
              </div>
              <div className="email">
                <Input
                  key="contact_email"
                  label="Contact Email"
                  value={formData.contact_email}
                  onChange={handleChange}
                  size="small"
                  name="contact_email"
                  placeholder="Contact Email"
                  validator={fieldsChanged && validateEmail}
                  className="InputGroup--vTop Input--required"
                />
              </div>
            </div>
            <div className="los-row">
              <Input
                placeholder="PAN number"
                value={formData.pan_number}
                onChange={handleChange}
                label="PAN Number"
                key="pan_number"
                name="pan_number"
                size="small"
                className="InputGroup--vTop Input--required"
                validator={fieldsChanged && validatePanNumber}
              />
            </div>
            <div className="los-row">
              <Input.Textarea
                placeholder="Address"
                value={formData.address}
                onChange={handleChange}
                label="Residential Address"
                name="address"
                key="address"
                size="large"
                className="InputGroup--vTop Input--required"
                validator={fieldsChanged && validateAddress}
              />
            </div>
            <div className="flex pincode los-row">
              <Input
                name="pincode"
                label="Pincode"
                key="pincode"
                placeholder="Pincode"
                value={formData.pincode}
                onChange={handleChange}
                className="InputGroup--vTop Input--required"
                size="small"
                maxlength="6"
                validator={fieldsChanged && validatePinCode}
              />
              <div className="city-state">
                {!pincodeError && formData.city && `${formData.city}, ${states[formData.state]}`}
              </div>
            </div>
            {pincodeError && (
              <div class="flex">
                <Input
                  value={formData.city}
                  onChange={handleChange}
                  placeholder="city"
                  name="city"
                  label="City"
                  required
                  class="InputGroup--vTop"
                  size="small"
                />
                <Input.Select
                  value={formData.state}
                  onChange={handleChange}
                  size="small"
                  placeholder="state"
                  name="state"
                  options={statesOptions}
                  required
                  label="State"
                  class="InputGroup--vTop"
                />
              </div>
            )}

            <div style={{ margin: '12px 0' }}>
              <BureauCompliance name={getNameForConsent} />
            </div>
            <div>
              <AsyncBtn.Primary
                type="submit"
                className="btn btn-primary check-cta"
                onClick={handleSubmit}
                isPending={isPending}
              >
                Check your Eligibility
                <i className="i i-chevron-right" />
              </AsyncBtn.Primary>
            </div>
          </Form>
        </>
      )}
    </div>
  );
};

PersonalDetailsForm.propTypes = {};

const mapStateToProps = (state) => ({
  initialFormValues: personalInfoSelector(state.session.user),
  user: state.session.user,
  loanApplicationDetails: state.loanApplicationDetails,
});

export default connect(mapStateToProps, {
  showNotification: fnShowNotification,
})(PersonalDetailsForm);
