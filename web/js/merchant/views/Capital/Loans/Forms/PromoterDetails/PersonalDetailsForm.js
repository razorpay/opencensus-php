import React from 'react';
import { connect } from 'react-redux';

import Input from 'common/new-ui/Input';
import Form from 'common/new-ui/Form';
import { AsyncBtn } from 'common/new-ui/Button';
import Datetime from 'react-datetime';
import { GENDER_OPTIONS, NOOP } from '../../constants';
import { showNotification as fnShowNotification } from 'merchant_common/reducers/notifications';
import { states } from 'merchant/helpers/data';
import { getCityAndState } from '../LosOnboarding/PersonalDetailsForm';
import BureauCompliance from 'merchant/views/Capital/Loans/BureauCompliance';
import moment from 'moment';
import {
  validateAddress,
  validateEmail,
  validateFirstName,
  validateMobile,
  validatePanNumber,
  validatePinCode,
  validateLastName,
} from '../Validators';

import { trackCheckEligibilityCta, trackMajorStackholderFill } from '../ga';
import { getPersonalFormError } from '../Helpers/getPersonalFormErrors';
import { statesOptions } from '../Helpers/getStatesOptions';
import { isValidPinCode } from 'common/utils/validators';

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

const PersonalDetailsForm = ({
  canModify,
  merchantId,
  isPending,
  loanApplicationDetails,
  handlePersonalSubmit,
  initialFormValues,
  showNotification,
}) => {
  const {
    promoter_details: {
      data: {
        applicant: {
          kyc: {
            first_name,
            second_name,
            date_of_birth,
            gender,
            pan_number,
            majority_stakeholder,
          } = {},
          phones,
          emails,
          addresses,
        } = {},
      } = {},
    } = {},
  } = loanApplicationDetails;

  const [formData, setFormData] = React.useState({
    first_name,
    second_name,
    gender,
    pan_number,
    date_of_birth,
    contact_number: phones?.[0]?.phone_number || '',
    contact_email: emails?.[0]?.email_id || '',
    address: addresses?.[0]?.address_line1 + (addresses?.[0]?.address_l2 || ''),
    pincode: addresses?.[0]?.pincode || '',
    city: addresses?.[0]?.city || '',
    state: addresses?.[0]?.state || Object.entries(states)[0][0],
  });
  const [majorityStakeholder, setMajorityStakeholder] = React.useState(majority_stakeholder);
  const [pincodeError, setPincodeError] = React.useState(false);
  const [fieldsChanged, setFieldsChanged] = React.useState(false);
  const getNameForConsent = `${formData.first_name} ${formData.second_name}`;

  const derivePromoterDetailsFormValues = (applicant) => {
    const data = {
      first_name: applicant.kyc.first_name,
      second_name: applicant.kyc.second_name,
      date_of_birth: applicant.kyc.date_of_birth,
      gender: applicant.kyc.gender || GENDER_OPTIONS[0].name,
      contact_number: applicant.phones[0].phone_number,
      contact_email: applicant.emails[0].email_id,
      address: applicant.addresses[0].address_line1 + (applicant.addresses[0].address_l2 || ''),
      pincode: applicant.addresses[0].pincode,
      city: applicant.addresses[0].city,
      state: applicant.addresses[0].state || Object.entries(states)[0][0],
      pan_number: applicant.kyc.pan_number,
    };
    setFormData(data);
  };

  function fetchDetails() {
    if (loanApplicationDetails.promoter_details.data.applicant) {
      derivePromoterDetailsFormValues(loanApplicationDetails.promoter_details.data.applicant);
    } else {
      setFormData(initialFormValues);
    }
  }

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
    trackMajorStackholderFill(merchantId, value ? 'Yes' : 'No', 'Detailed');
    setMajorityStakeholder(value);
  };

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
    if (canModify) {
      if (isValidPinCode(formData.pincode)) {
        loadCityAndState();
      }
    }
  }, [formData.pincode]);

  React.useEffect(() => {
    if (canModify) {
      if (majorityStakeholder) fetchDetails();
      else setFormData(initialFormData);
    } else {
      fetchDetails();
    }
  }, [majorityStakeholder]);

  const handleSubmit = () => {
    const loanId = loanApplicationDetails.applications.data.applications[0].id;
    trackCheckEligibilityCta(merchantId, majorityStakeholder ? 'Yes' : 'No', 'Detailed', loanId);

    let errors = [];

    if (canModify) {
      errors = getPersonalFormError(formData);
      if (errors.length) {
        showNotification({
          type: 'error',
          message: errors,
        });
      } else {
        handlePersonalSubmit({
          ...formData,
          majority_stakeholder: majorityStakeholder,
        });
      }
    } else {
      handlePersonalSubmit({
        ...formData,
        majority_stakeholder: majorityStakeholder,
      });
    }
  };

  const isValidDate = (current) => {
    const age = moment().diff(current, 'years');
    return age <= 65 && age >= 18;
  };

  return (
    <>
      {majorityStakeholder !== null && majorityStakeholder !== undefined && (
        <div className="promoter-details-header">
          <div className="promoter-details-header-left flex">
            <div className="promoter-details-header-text">
              Do you own atleast 20% of <br /> business?
            </div>
            <Input.Radio
              className="Input--vTop promoter-details-header-radios"
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
              disabled={!canModify}
            />
          </div>
          <div className="info flex">
            <span className="info-border" />
            <div className="info-text">
              {majorityStakeholder
                ? 'Business loans requires KYC details of the key stakeholder. Kindly click on the option No if you are not a key stakeholder.'
                : 'You hereby confirm that, you are an individual with significant responsibility for managing the business (e.g: CEO, CFO, COO, Partner, VP, Treasurer, or CA) and is applying on behalf of the key stakeholder.'}
            </div>
          </div>
          <h5 className="heading">
            {majorityStakeholder
              ? 'Please confirm the business owner details availabe with us'
              : 'Please provide the key stakeholder details with more than 20% business ownership'}
          </h5>
        </div>
      )}
      <Form
        layout="tabular"
        className="Form Form--tabular loan-application-form personal-details-form"
        onSubmit={NOOP}
        key={majorityStakeholder}
      >
        <Input.Group label="Name" className="InputGroup--inline">
          <div className="Input-content">
            <Input
              value={formData.first_name}
              onChange={handleChange}
              name="first_name"
              placeholder="First Name"
              disabled={!canModify}
              size="small"
              validator={fieldsChanged && validateFirstName}
            />
            <Input
              value={formData.second_name}
              onChange={handleChange}
              name="second_name"
              placeholder="Second Name"
              disabled={!canModify}
              size="small"
              validator={fieldsChanged && validateLastName}
            />
          </div>
        </Input.Group>
        <Input.Group label="Date of Birth" className="InputGroup--inline dob">
          <div
            className={`Input-content dob-wrapper ${
              canModify && formData.date_of_birth && !isValidDate(formData.date_of_birth)
                ? 'error'
                : ''
            }`}
          >
            <Datetime
              value={formData.date_of_birth}
              onChange={(value) =>
                handleChange({
                  target: {
                    name: 'date_of_birth',
                    value,
                  },
                })
              }
              dateFormat="YYYY-MM-DD"
              closeOnSelect={true}
              allowToday={false}
              allowAllDates={false}
              disablePastDates={false}
              viewMode="years"
              disableFutureDates
              isValidDate={isValidDate}
              size="small"
              timeFormat={false}
              placement="topLeft"
              inputProps={{
                placeholder: 'Select a date',
                disabled: !canModify,
              }}
              isInline
            />
            {canModify && formData.date_of_birth && !isValidDate(formData.date_of_birth) && (
              <div class="Input-error d-block">To apply, you must be between 18 to 65 years</div>
            )}
          </div>
          <Input.Select
            value={formData.gender}
            onChange={handleChange}
            size="small"
            name="gender"
            disabled={!canModify}
            options={GENDER_OPTIONS}
            className="InputGroup--vTop gender"
          />
        </Input.Group>
        <Input.Group label="Contact Details" className="InputGroup--inline">
          <div className="Input-content flex contact">
            <div className="contact_number">
              <Input
                addonBefore={<span>+91</span>}
                disabled={!canModify}
                type="number"
                value={formData.contact_number}
                onChange={handleChange}
                name="contact_number"
                placeholder="Contact Number"
                validator={fieldsChanged && validateMobile}
              />
            </div>
            <Input
              value={formData.contact_email}
              onChange={handleChange}
              name="contact_email"
              disabled={!canModify}
              size="small"
              className="email"
              placeholder="Contact Email"
              validator={fieldsChanged && validateEmail}
            />
          </div>
        </Input.Group>
        <div className="pan_number">
          <Input
            value={formData.pan_number}
            onChange={handleChange}
            name="pan_number"
            label="PAN Number"
            size="small"
            disabled={!canModify}
            placeholder="PAN Number"
            validator={fieldsChanged && validatePanNumber}
          />
        </div>
        <div className="address">
          <Input.Textarea
            placeholder="Address"
            value={formData.address}
            onChange={handleChange}
            name="address"
            label="Residential Address"
            size="large"
            disabled={!canModify}
            validator={fieldsChanged && validateAddress}
          />
        </div>
        <div className="flex">
          <Input
            value={formData.pincode}
            onChange={handleChange}
            name="pincode"
            label="Pincode"
            placeholder="Pincode"
            size="small"
            maxlength="6"
            disabled={!canModify}
            validator={fieldsChanged && validatePinCode}
          />
          <div className={`city-state ${pincodeError ? 'error' : ''}`}>
            {!pincodeError && formData.city && `${formData.city}, ${states[formData.state]}`}
          </div>
        </div>
        {pincodeError && (
          <Input.Group label="City" className="InputGroup--inline city-state-error">
            <div className="Input-content flex">
              <Input
                value={formData.city}
                onChange={handleChange}
                size="small"
                name="city"
                className="InputGroup--vTop"
                disabled={!canModify}
              />
              <Input.Select
                value={formData.state}
                onChange={handleChange}
                size="small"
                placeholder="state"
                name="state"
                options={statesOptions}
                class="InputGroup--vTop"
                disabled={!canModify}
              />
            </div>
          </Input.Group>
        )}
        <div className="terms">
          <BureauCompliance name={getNameForConsent} />
        </div>
        <div className="loan-application-form-footer">
          <AsyncBtn.Primary
            type="submit"
            className="btn btn-primary"
            onClick={handleSubmit}
            isPending={isPending}
          >
            {canModify ? 'Save & Continue' : 'Continue'}
            <i className="i i-chevron-right" />
          </AsyncBtn.Primary>
        </div>
      </Form>
    </>
  );
};

const mapStateToProps = (state) => ({
  initialFormValues: personalInfoSelector(state.session.user),
  loanApplicationDetails: state.loanApplicationDetails,
});

export default connect(mapStateToProps, {
  showNotification: fnShowNotification,
})(PersonalDetailsForm);
