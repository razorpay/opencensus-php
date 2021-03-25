import { FormikErrors } from 'formik';
import React from 'react';
import { Select, Option } from 'v2/components/Select';
import { BusinessTypes } from '../../Constants/OnboardingConstants';
// import { isL1Submitted } from '../../services/utils';

export interface BusinessTypePropsT {
  errorText?: string | false | string[] | FormikErrors<any> | FormikErrors<any>[] | undefined;
  value: string;
  onChange: (value: string) => void;
  onboardingMilestone: string;
  disabled?: boolean;
}

const BusinessType: React.FC<BusinessTypePropsT> = ({
  value,
  errorText,
  onChange,
  // onboardingMilestone,
  disabled,
}) => {
  return (
    <Select
      label="Business Type"
      errorText={errorText}
      value={value}
      onChange={onChange}
      disabled={disabled}
      bottomSheetHeaderText="SELECT BUSINESS TYPE"
    >
      {Object.keys(BusinessTypes)
        .map((business_type) => {
          // if (isL1Submitted(onboardingMilestone) || onboardingMilestone !== null) {
          //   if (value === '11') {
          //     if (business_type !== '11') {
          //       return null;
          //     }
          //   } else if (business_type === '11') {
          //     return null;
          //   }
          // }
          return (
            <Option key={business_type} value={business_type} label={BusinessTypes[business_type]}>
              {BusinessTypes[business_type]}
            </Option>
          );
        })
        .filter((item) => !!item)}
    </Select>
  );
};

export default BusinessType;
