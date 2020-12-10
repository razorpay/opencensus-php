import {
  isUnregisteredBusiness,
  CIN_BusinessTypes,
  LLPIN_BusinessTypes,
} from '../Constants/OnboardingConstants';

export const getLabel = (field, data) => {
  const businessType = data.business_overview.business_type.value;
  let label = '';
  switch (field) {
    case 'promoter_pan':
      label = isUnregisteredBusiness(businessType)
        ? "Business Owner's PAN"
        : 'Authorised Signatory PAN';
      break;
    case 'promoter_pan_name':
      label = isUnregisteredBusiness(businessType)
        ? "Business Owner's Name"
        : 'Authorised Signatory Name';
      break;
    case 'company_cin':
      if (CIN_BusinessTypes.includes(Number(businessType))) {
        label = 'Company Identification Number (CIN)';
      } else if (LLPIN_BusinessTypes.includes(Number(businessType))) {
        label = 'LLP Identification Number (LLPIN)';
      } else label = '';
      break;
    default:
      label = '';
  }
  return label;
};

export const getHelpText = (field, data) => {
  const businessType = data.business_overview.business_type.value;
  let helpText = '';
  switch (field) {
    case 'promoter_pan':
      helpText = isUnregisteredBusiness(businessType) ? '' : 'PAN of one of the directors';
      break;
    default:
      helpText = '';
  }
  return helpText;
};
