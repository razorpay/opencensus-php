import { FormikErrors } from 'formik';
import React from 'react';
import { Select, Option } from 'common/components/Select';
import { isL1Submitted } from '../../services/utils';
import { useApp } from 'common/context/App';
import useBusinessTypes from '../../hooks/useBusinessTypes';

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
  onboardingMilestone,
  disabled,
}) => {
  const { experiments } = useApp();
  const { data } = useBusinessTypes();
  return (
    <Select
      label="Business Type"
      errorText={errorText}
      value={value}
      onChange={onChange}
      disabled={disabled}
      bottomSheetHeaderText="SELECT BUSINESS TYPE"
    >
      {data?.map((item) => {
        if (isL1Submitted(onboardingMilestone) && experiments.isInstantActivationEnabled) {
          if (value === '11') {
            if (item.id !== '11') {
              return null;
            }
          } else if (item.id === '11') {
            return null;
          }
        }
        return (
          <React.Fragment key={item.id}>
            {item.status === 'active' && item.label !== 'Individual' && (
              <Option key={item.id} value={String(item.id)} label={item.label}>
                {item.label}
              </Option>
            )}
          </React.Fragment>
        );
      })}
    </Select>
  );
};

export default BusinessType;
