import {
  validateAddress,
  validateEmail,
  validateFirstName,
  validateMobile,
  validatePanNumber,
  validatePinCode,
  validateLastName,
  validateDob,
} from '../Validators';

export const getPersonalFormError = (formData) => {
  const errors = [];

  Object.keys(formData).forEach((field) => {
    const value = formData[field];
    switch (field) {
      case 'first_name': {
        const error = validateFirstName(value);
        if (error) errors.push(error);
        break;
      }
      case 'second_name': {
        const error = validateLastName(value);
        if (error) errors.push(error);
        break;
      }
      case 'date_of_birth': {
        const error = validateDob(value);
        if (error) errors.push(error);
        break;
      }
      case 'contact_number': {
        const error = validateMobile(value);
        if (error) errors.push(error);
        break;
      }
      case 'contact_email': {
        const error = validateEmail(value);
        if (error) errors.push(error);
        break;
      }
      case 'pincode': {
        const error = validatePinCode(value);
        if (error) errors.push(error);
        break;
      }
      case 'address': {
        const error = validateAddress(value);
        if (error) errors.push(error);
        break;
      }
      case 'pan_number': {
        const error = validatePanNumber(value);
        if (error) errors.push(error);
        break;
      }
      case 'city': {
        const pincode_error = validatePinCode(formData.pincode);
        if (!pincode_error) {
          const error = !value;
          if (error)
            errors.push('Please check pincode again! No city/state found for given pincode');
        }
        break;
      }
      default:
        break;
    }
  });

  return errors;
};
