import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import Form from 'common/new-ui/Form';
import Input from 'common/new-ui/Input';
import Button from 'common/new-ui/Button';
import { states } from 'merchant/helpers/data';
import { trackBusinessFormContinueCta, trackBusinessFormTab } from '../ga';
import { BUSINESS_TYPES } from '../../constants';
import { getCityAndState } from '../LosOnboarding/PersonalDetailsForm';
import {
  validateBusinessAddress,
  validateGSTIN,
  validateCompanyPan,
  validatePinCode,
} from '../Validators';
import { isValidPinCode, isPanNumber } from 'common/utils/validators';
import { isValidGSTIN } from 'common/utils/rzp-utils';

const BusinessDetailsForm = ({
  merchantId,
  user,
  updatedValues,
  loanApplicationDetails,
  handleBusinessDetailsSubmit,
}) => {
  let businessDetails = {
    legal_name: user.business_name,
    deed_type: BUSINESS_TYPES[parseInt(user.business_type)],
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

  if (loanApplicationDetails.business_details.data.business) {
    businessDetails = loanApplicationDetails.business_details.data.business;
  }

  const { legal_name, deed_type, business_pan, gstin } = businessDetails;
  const { address_line1, city, state, pincode } = businessDetails.addresses[0];

  const [formData, setFormData] = React.useState({
    legal_name,
    deed_type,
    business_pan,
    gstin,
    address: address_line1,
    city,
    state,
    pincode,
  });
  const [pincodeError, setPincodeError] = React.useState(false);

  React.useEffect(() => {
    if (updatedValues) setFormData(updatedValues);
    else trackBusinessFormTab(merchantId);
  }, []);

  const handleChange = ({ target }) => {
    const { value, name } = target;
    setFormData({
      ...formData,
      [name]: value,
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
      setFormData({
        ...formData,
        city: '',
        state: '',
      });
      setPincodeError(true);
    }
  };

  React.useEffect(() => {
    const pincodeRegex = new RegExp('[1-9][0-9]{5}');
    if (pincodeRegex.test(formData.pincode)) {
      loadCityAndState();
    } else {
      setFormData({
        ...formData,
        city: '',
        state: '',
      });
    }
  }, [formData.pincode]);

  const isValidForm = () => {
    const mandatoryFields = ['legal_name', 'deed_type', 'address', 'city', 'state', 'pincode'];

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
          defaultValue={formData.legal_name}
          size="small"
          className="InputGroup--vTop Input--required"
          disabled
        />
        <Input
          label="Business type"
          defaultValue={formData.deed_type}
          size="small"
          className="InputGroup--vTop Input--required"
          disabled
        />
      </div>
      <div className="flex los-row">
        <Input
          label="Business PAN"
          value={formData.business_pan}
          onChange={handleChange}
          name="business_pan"
          size="small"
          className="InputGroup--vTop"
          validator={validateCompanyPan}
        />
        <div className="gstin">
          <Input
            label="GSTIN Number"
            value={formData.gstin}
            onChange={handleChange}
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
      <div className="flex pincode los-row">
        <Input
          name="pincode"
          label="Pincode"
          value={formData.pincode}
          onChange={handleChange}
          className="InputGroup--vTop Input--required"
          size="small"
          maxlength="6"
          validator={validatePinCode}
        />
        <div className={`city-state ${pincodeError ? 'error' : ''}`}>
          {pincodeError
            ? 'No record found! Please check pincode again'
            : formData.city && `${formData.city}, ${states[formData.state]}`}
        </div>
      </div>

      <Button.Primary
        type="submit"
        className="btn btn-primary no-margin new-onboarding-button"
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
