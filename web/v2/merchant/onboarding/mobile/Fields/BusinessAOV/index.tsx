import React from 'react';
import Text from '@razorpay/blade/src/atoms/Text';
import { Select, Option } from 'v2/components/Select';
import { FormikErrors } from 'formik';
import useAovRange from '../../hooks/useAovRange';

export interface BusinessAOVPropsT {
  errorText?: string | false | string[] | FormikErrors<any> | FormikErrors<any>[] | undefined;
  value: any;
  onChange?: (value: string) => void;
  disabled?: boolean;
}

const BusinessAOV: React.FC<BusinessAOVPropsT> = ({
  value,
  errorText,
  onChange,
  disabled = false,
}) => {
  const [aovRangeStatus, aovRangeData] = useAovRange();
  return (
    <Select
      label="Average Order Value"
      placeholder=""
      filterOptions={false}
      inputPlaceholder="Search Average Order Value"
      errorText={errorText}
      loading={aovRangeStatus === 'loading'}
      value={value ? `${value.min_aov}-${value.max_aov}` : ''}
      onChange={onChange}
      disabled={disabled}
      helpText="Range in which most of your payments would fall in"
      bottomSheetHeaderText="SELECT AVERAGE ORDER VALUE"
    >
      {aovRangeData
        ? aovRangeData.config.map((item, index) => {
            if (item.max === 0) {
              return (
                <Option key={index} value="More than ₹ 1,00,000" label="More than ₹ 1,00,000">
                  <Text size="medium" color="shade.970">
                    More than ₹ 1,00,000
                  </Text>
                </Option>
              );
            } else {
              return (
                <Option
                  key={index}
                  value={`${item.min}-${item.max}`}
                  label={`₹ ${item.min} - ₹ ${item.max}`}
                >
                  <Text size="medium" color="shade.970">
                    {`₹ ${item.min} - ₹ ${item.max}`}
                  </Text>
                </Option>
              );
            }
          })
        : null}
    </Select>
  );
};

export default BusinessAOV;
