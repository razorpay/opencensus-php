import React, { useState, useRef } from 'react';
import Text from '@razorpay/blade-old/src/atoms/Text';
import { Select, Option } from 'v2/components/Select';
import { FormikErrors } from 'formik';
import { debounce } from '../../services/utils';
import useBusinessName from '../../hooks/useBusinessName';

interface NameData {
  company_name?: string;
  identity_number?: string;
  identity_type?: string;
}

interface BusinessNamePropsT {
  errorText?: string | false | string[] | FormikErrors<any> | FormikErrors<any>[] | undefined;
  value: string;
  onModalClosed: (value: NameData) => void;
}

const BusinessName: React.FC<BusinessNamePropsT> = ({ value = '', onModalClosed, errorText }) => {
  const [inputValue, setInputValue] = useState('');
  const businessNameData = useRef({});

  const onInputChange = debounce((val) => {
    businessNameData.current = { company_name: val };
    setInputValue(val);
  }, 200);

  const [businessNamesStatus, businessNamesData] = useBusinessName(inputValue);

  const onChange = (val) => {
    const selectedBusinessNameData = businessNamesData?.results.find(
      (_) => _.identity_number === val,
    );
    businessNameData.current = selectedBusinessNameData;
  };

  return (
    <Select
      label="Business Name"
      searchable={true}
      placeholder=""
      inputPlaceholder="Search Business Name"
      errorText={errorText}
      loading={businessNamesStatus === 'loading'}
      value={value}
      helpText="As mentioned in the PAN"
      onChange={onChange}
      onInputChange={onInputChange}
      disabled={false}
      filterOptions={false}
      bottomSheetHeaderText="SELECT BUSINESS NAME"
      showInputValueInSelectedLabel
      onModalClosed={() => {
        onModalClosed(businessNameData.current);
      }}
    >
      {businessNamesData?.results?.length
        ? businessNamesData.results.map(({ identity_number, company_name }) => (
            <Option key={identity_number} label={company_name} value={identity_number}>
              <Text size="medium" color="shade.970">
                {company_name}
              </Text>
            </Option>
          ))
        : null}
    </Select>
  );
};

export default BusinessName;
