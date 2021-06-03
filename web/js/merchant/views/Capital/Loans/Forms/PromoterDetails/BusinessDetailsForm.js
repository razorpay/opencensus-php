import React from 'react';
import { connect } from 'react-redux';

import Input from 'common/new-ui/Input';
import Form from 'common/new-ui/Form';
import Button from 'common/new-ui/Button';

import { closeModal, openModal } from 'merchant_common/reducers/modals';

import { states } from 'merchant/helpers/data';

import { isValidPinCode, isPanNumber } from 'common/utils/validators';
import { isValidGSTIN } from 'common/utils/rzp-utils';
import { getCityAndState } from '../LosOnboarding/PersonalDetailsForm';

const BusinessDetailsForm = ({
  canModify,
  loanApplicationDetails,
  handleBusinessSubmit,
  updatedValues,
}) => {
  const {
    legal_name,
    deed_type,
    business_pan,
    gstin,
    addresses,
  } = loanApplicationDetails.business_details.data.business;

  const { address_line1, city, state, pincode } = addresses[0];

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
    if (updatedValues) {
      setFormData(updatedValues);
    }
  }, []);

  const handleChange = ({ target }) => {
    const { value, name } = target;
    setFormData({
      ...formData,
      [name]: value,
    });
  };

  const handleSubmit = () => {
    handleBusinessSubmit(formData);
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
      validGSTIN &&
      validBusinessPan
    );
  };

  return (
    <div className="business-form-wrapper">
      <Form
        layout="tabular"
        className="Form Form--tabular loan-application-form business-details-form"
      >
        <Input.Group
          className="InputGroup--inline InputGroup--vTop"
          label="Business legal name"
          required
        >
          <Input
            defaultValue={formData.legal_name}
            size="small"
            className="InputGroup--vTop"
            disabled
          />
        </Input.Group>
        <Input.Group className="InputGroup--inline InputGroup--vTop" label="Business type" required>
          <Input
            defaultValue={formData.deed_type}
            size="small"
            className="InputGroup--vTop"
            disabled
          />
        </Input.Group>
        <Input.Group className="InputGroup--inline InputGroup--vTop" label="Business PAN">
          <Input
            value={formData.business_pan}
            onChange={handleChange}
            size="small"
            className="InputGroup--vTop"
            name="business_pan"
            disabled={!canModify}
          />
        </Input.Group>
        <Input.Group className="InputGroup--inline InputGroup--vTop gstin" label="GSTIN Number">
          <Input
            value={formData.gstin}
            onChange={handleChange}
            size="small"
            className="InputGroup--vTop"
            name="gstin"
            disabled={!canModify}
          />
        </Input.Group>
        <Input.Group
          className="InputGroup--inline InputGroup--vTop address required"
          label="Business Address"
          required
        >
          <Input.Textarea
            placeholder="Address"
            value={formData.address}
            onChange={handleChange}
            size="large"
            className="InputGroup--vTop"
            name="address"
            disabled={!canModify}
          />
        </Input.Group>
        <Input.Group
          className="InputGroup--inline InputGroup--vTop"
          label="Residential Pincode"
          required
        >
          <Input
            value={formData.pincode}
            onChange={handleChange}
            size="small"
            name="pincode"
            className="InputGroup--vTop"
            disabled={!canModify}
            maxlength="6"
          />
          <div className={`city-state ${pincodeError ? 'error' : ''}`}>
            {pincodeError
              ? 'No record found! Please check pincode again'
              : formData.city && `${formData.city}, ${states[formData.state]}`}
          </div>
        </Input.Group>

        <Button.Primary
          type="submit"
          className="btn btn-primary no-margin new-onboarding-button"
          disabled={!isValidForm()}
          onClick={handleSubmit}
        >
          Continue <i className="i i-chevron-right" />
        </Button.Primary>
      </Form>
    </div>
  );
};

const mapStateToProps = (state) => ({
  loanApplicationDetails: state.loanApplicationDetails,
});

export default connect(mapStateToProps, {
  openModal,
  closeModal,
})(BusinessDetailsForm);
