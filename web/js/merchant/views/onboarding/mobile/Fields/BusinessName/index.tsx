import React, { useState, useRef } from 'react';
import styled from 'styled-components';
import { getColor } from '@razorpay/blade-old/src/_helpers/theme';
import Text from '@razorpay/blade-old/src/atoms/Text';
import View from '@razorpay/blade-old/src/atoms/View';
import Space from '@razorpay/blade-old/src/atoms/Space';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import { Select, Option } from 'common/components/Select';
import { FormikErrors } from 'formik';
import Icon from '@razorpay/blade-old/src/atoms/Icon';
import { debounce } from '../../services/utils';
import useBusinessName from '../../hooks/useBusinessName';

interface INameData {
  company_name?: string;
  identity_number?: string;
  identity_type?: string;
}

interface IBusinessNameProps {
  errorText?: string | false | string[] | FormikErrors<any> | FormikErrors<any>[] | undefined;
  businessNameValue: string;
  updateBusinessName: (value: INameData) => void;
  disabled?: boolean;
  onInputBlur: (value: string) => void;
}

const StyledSeparator = styled(View)`
  height: 1px;
  width: 100%;
  background-color: ${({ theme }) => getColor(theme, 'shade.920')};
`;

const BusinessName = ({
  businessNameValue = '',
  updateBusinessName,
  errorText,
  disabled = false,
  onInputBlur,
}: IBusinessNameProps): React.ReactElement => {
  const [inputValue, setInputValue] = useState(businessNameValue);
  const businessNameData = useRef({});

  const onInputChange = debounce((val) => {
    businessNameData.current = { company_name: val };
    setInputValue(val);
  }, 200);

  const [businessNamesStatus, businessNamesData] = useBusinessName(inputValue);

  const onChange = (val) => {
    const selectedBusinessNameData = businessNamesData?.results.find(
      ({ company_name }) => company_name === val,
    );
    if (selectedBusinessNameData) {
      businessNameData.current = selectedBusinessNameData;
    }
    updateBusinessName(businessNameData.current);
  };

  const getOptions = () => {
    if (businessNamesData?.results?.length) {
      return businessNamesData.results.map(({ identity_number, company_name }) => (
        <Option key={identity_number} label={company_name} value={company_name}>
          <Text size="medium" color="shade.970">
            {company_name}
          </Text>
        </Option>
      ));
    }

    if (inputValue.length >= 3) {
      if (businessNameValue === inputValue) {
        return (
          <Option key={0} label={businessNameValue} value={businessNameValue}>
            <Text size="medium" color="shade.970">
              {businessNameValue}
            </Text>
          </Option>
        );
      } else {
        return (
          <>
            <Option key="1" value={inputValue} label={inputValue}>
              <Flex justifyContent="space-between">
                <View>
                  <Text size="medium" color="shade.970">
                    {`Add '${inputValue}'`}
                  </Text>
                  <Text align="center">
                    <Icon name="chevronRight" />
                  </Text>
                </View>
              </Flex>
              <Space margin={[1, 0, 1, 0]}>
                <StyledSeparator />
              </Space>
            </Option>
            <Option key="0" value="none" label="none" disabled={true}>
              <Space margin={[6, 0, 2, 0]}>
                <View>
                  <Text align="center">
                    <Icon name="search" />
                  </Text>
                  <Space margin={[1, 0, 0, 0]}>
                    <Text align="center" size="small">
                      <View>No match found. Don’t worry you can</View>
                      <View>add it manually</View>
                    </Text>
                  </Space>
                </View>
              </Space>
            </Option>
          </>
        );
      }
    }

    return (
      <Option key="0" value="none" label="none" disabled={true}>
        <Space margin={[6, 0, 2, 0]}>
          <View>
            <Text align="center">
              <Icon name="search" />
            </Text>
            <Space margin={[1, 0, 0, 0]}>
              <Text align="center" size="small">
                <View>Start typing your business name and we’ll</View>
                <View>search it in the government database</View>
              </Text>
            </Space>
          </View>
        </Space>
      </Option>
    );
  };

  return (
    <Select
      label="Business Name"
      searchable={true}
      placeholder=""
      inputPlaceholder="Search Business Name"
      errorText={errorText}
      loading={businessNamesStatus === 'loading'}
      value={businessNameValue}
      helpText="As mentioned in the PAN"
      onChange={onChange}
      onInputChange={onInputChange}
      onInputBlur={onInputBlur}
      disabled={disabled}
      filterOptions={false}
      bottomSheetHeaderText="SELECT BUSINESS NAME"
    >
      {getOptions()}
    </Select>
  );
};

export default BusinessName;
