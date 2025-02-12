import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import { states } from 'merchant/helpers/data';
import { trackBusinessFormContinueCta, trackBusinessFormTab } from '../ga';
import {
  BUSINESS_TYPES,
  NOOP,
  BUSINESS_NATURE_TYPES,
  PROPERTY_OWNERSHIP_TYPES,
} from '../../constants';
import { getCityAndState } from '../LosOnboarding/PersonalDetailsForm';
import {
  validateBusinessAddress,
  validateGSTIN,
  validateCompanyPan,
  validatePinCode,
} from '../Validators';
import { isValidPinCode, isPanNumber } from 'common/utils/validators';
import { isValidGSTIN } from 'common/utils/rzp-utils';
import { statesOptions } from '../Helpers/getStatesOptions';
import moment from 'moment';
import { disableFutureMonths } from 'merchant/views/Capital/utils';

const INITIAL_VALUES = {
  legal_name: '',
  deed_type: '',
  business_pan: '',
  gstin: '',
  address: '',
  city: '',
  state: Object.entries(states)[0][0],
  pincode: '',
  date_of_incorporation: '',
  nature: '',
  ownership: '',
};

const BusinessDetailsForm = ({
  merchantId,
  user,
  updatedValues,
  loanApplicationDetails,
  handleBusinessDetailsSubmit,
}) => {
  const [formData, setFormData] = React.useState(INITIAL_VALUES);
  const [pincodeError, setPincodeError] = React.useState(false);

  const loadFormData = () => {
    let businessDetails = {
      legal_name: user.business_name,
      deed_type: BUSINESS_TYPES[parseInt(user.business_type, 10)],
      business_pan: user.company_pan,
      addresses: [
        {
          address_line1: user.business_registered_address,
          address_line2: user.business_registered_address_l2,
          city: user.business_registered_city,
          state: user.business_registered_state,
          pincode: user.business_registered_pin,
        },
      ],
    };

    if (loanApplicationDetails?.business_details?.data?.business) {
      businessDetails = loanApplicationDetails?.business_details?.data?.business;
    }

    const {
      legal_name,
      deed_type,
      business_pan,
      gstin,
      date_of_incorporation,
      nature,
      ownership,
    } = businessDetails;
    const { address_line1, city, state, pincode } = businessDetails.addresses[0];

    setFormData({
      legal_name,
      deed_type,
      business_pan,
      gstin,
      address: address_line1,
      city,
      state: state ? state : Object.entries(states)[0][0],
      pincode,
      date_of_incorporation,
      nature,
      ownership,
    });
  };

  React.useEffect(() => {
    if (updatedValues) setFormData(updatedValues);
    else {
      trackBusinessFormTab(merchantId);
      loadFormData();
    }
  }, []);

  const handleChange = ({ target }) => {
    const { value, name } = target;
    setFormData({
      ...formData,
      [name]: value,
    });
  };

  const handleDateChange = (value) => {
    handleChange({
      target: {
        name: 'date_of_incorporation',
        value: moment(value).format('YYYY-MM-DD'),
      },
    });
  };

  const handleSubmit = () => {
    trackBusinessFormContinueCta(merchantId);
    handleBusinessDetailsSubmit(formData);
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
    if (isValidPinCode(formData.pincode)) {
      loadCityAndState();
    }
  }, [formData.pincode]);

  const isValidForm = () => {
    const mandatoryFields = [
      'legal_name',
      'deed_type',
      'address',
      'city',
      'state',
      'pincode',
      'nature',
      'date_of_incorporation',
      'ownership',
    ];

    const validBusinessPan = formData.business_pan ? isPanNumber(formData.business_pan) : true;
    const validGSTIN = formData.gstin ? isValidGSTIN(formData.gstin) : true;

    return (
      mandatoryFields.every((field) => !!formData[field]) &&
      isValidPinCode(formData.pincode) &&
      validBusinessPan &&
      validGSTIN
    );
  };

  return (
    <Form className="los-business-details-form" onSubmit={() => {}}>
      <div className="flex los-row" style={{ marginTop: '24px' }}>
        <Input
          label="Business legal name"
          value={formData.legal_name}
          onChange={NOOP}
          size="small"
          className="InputGroup--vTop Input--required"
          disabled
        />
        <Input
          label="Business type"
          value={formData.deed_type}
          onChange={NOOP}
          size="small"
          className="InputGroup--vTop Input--required"
          disabled
        />
      </div>
      <div className="flex los-row">
        <Input.Select
          label="Nature Of Business "
          value={formData.nature || {}}
          onChange={handleChange}
          size="small"
          name="nature"
          options={BUSINESS_NATURE_TYPES}
          className="InputGroup--vTop Input--required"
        />
        <Input.Group
          label="Date of Incorporation"
          className="Input--small InputGroup--inline InputGroup--vTop Input--required"
        >
          <div className="Input-content">
            <div className="Input-elWrapper">
              <Input.ToCalendar
                allowToday
                placeholder="DD-MM-YYYY"
                onChange={handleDateChange}
                addonAfter={<i className="i i-date-range" />}
                placement="bottomLeft"
                value={formData.date_of_incorporation}
                disabledDate={disableFutureMonths}
              />
            </div>
          </div>
        </Input.Group>
      </div>

      <div className="flex los-row">
        <Input
          label="Business PAN"
          value={formData.business_pan}
          onChange={handleChange}
          name="business_pan"
          size="small"
          placeholder="Business PAN"
          className="InputGroup--vTop"
          validator={validateCompanyPan}
        />
        <div className="gstin">
          <Input
            label="GSTIN Number"
            value={formData.gstin}
            onChange={handleChange}
            placeholder="GSTIN Number"
            name="gstin"
            size="small"
            className="InputGroup--vTop"
            validator={validateGSTIN}
          />
        </div>
      </div>
      <div className="address los-row">
        <Input.Textarea
          placeholder="Address"
          key="address"
          value={formData.address}
          onChange={handleChange}
          label="Business Address"
          name="address"
          size="large"
          className="InputGroup--vTop Input--required"
          validator={validateBusinessAddress}
        />
      </div>
      <div className={`flex pincode los-row ${pincodeError ? 'error' : ''}`}>
        <Input
          name="pincode"
          label="Pincode"
          value={formData.pincode}
          onChange={handleChange}
          placeholder="Pincode"
          className="InputGroup--vTop Input--required"
          size="small"
          maxlength="6"
          validator={validatePinCode}
        />
        <div className="city-state">
          {!pincodeError && formData.city && `${formData.city}, ${states[formData.state]}`}
        </div>
      </div>
      {pincodeError && (
        <div className="flex los-row">
          <Input
            value={formData.city}
            onChange={handleChange}
            placeholder="city"
            name="city"
            label="City"
            required
            className="InputGroup--vTop"
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
            className="InputGroup--vTop"
          />
        </div>
      )}

      <div className="flex los-row">
        <Input.Select
          label="Property Ownership"
          value={formData.ownership || {}}
          onChange={handleChange}
          size="small"
          name="ownership"
          options={PROPERTY_OWNERSHIP_TYPES}
          className="InputGroup--vTop Input--required"
        />
      </div>

      <Button.Primary
        type="submit"
        className="btn btn-primary continue-cta"
        onClick={handleSubmit}
        disabled={!isValidForm()}
      >
        Confirm Business Details <i className="i i-chevron-right" />
      </Button.Primary>
    </Form>
  );
};

BusinessDetailsForm.propTypes = {
  handleBusinessDetailsSubmit: PropTypes.func.isRequired,
};

const mapStateToProps = (state) => ({
  user: state.session.user,
  loanApplicationDetails: state.loanApplicationDetails,
});

export default connect(mapStateToProps)(BusinessDetailsForm);
