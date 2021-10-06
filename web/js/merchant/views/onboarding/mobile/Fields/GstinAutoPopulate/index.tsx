import React, { useState, useEffect, useRef, ReactElement } from 'react';
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
import { analyticsTrack } from 'common/services/tracking/segment';
import { useApp } from 'common/context/App';

interface GstinDetailsPropsT {
  gstinList?: any;
  defaultGstin?: string;
}

interface GstinAutoPopulatePropsT {
  gstin: string;
  updateGstin: (value: string) => void;
  hasGSTIN: boolean;
  errorText?: string | false | string[] | FormikErrors<any> | FormikErrors<any>[] | undefined;
  disabled?: boolean;
  gstinDetails?: GstinDetailsPropsT;
  location?: string;
}

const StyledSeparator = styled(View)`
  height: 1px;
  width: 100%;
  background-color: ${({ theme }) => getColor(theme, 'shade.920')};
`;

const GstinAutoPopulate: React.FC<GstinAutoPopulatePropsT> = ({
  gstin,
  errorText,
  disabled = false,
  updateGstin,
  hasGSTIN,
  gstinDetails,
  location = '',
}) => {
  const { user } = useApp();
  const defaultGstin = gstinDetails?.defaultGstin || '';
  const gstinValue = gstin || defaultGstin;
  const [isDescriptionVisible, setIsDescriptionVisible] = useState(!gstin && !!defaultGstin);
  const [inputValue, setInputValue] = useState(gstinValue);
  const showFullList = useRef(true);
  const gstinList = gstinDetails?.gstinList || [];

  useEffect(() => {
    if (!gstin && !!defaultGstin && !hasGSTIN) {
      if (!isDescriptionVisible) {
        setIsDescriptionVisible(true);
      }
      updateGstin(defaultGstin);
    }
  }, [gstin, hasGSTIN]);

  useEffect(() => {
    if (isDescriptionVisible) {
      analyticsTrack({
        objectName: 'Default value from autopopulated gstin',
        actionName: 'displayed',
        screen: 'home page',
        user,
        eventAction: 'initiated',
        properties: {
          location,
          gstinListLength: gstinList.length,
        },
      });
    }
  }, []);

  const onInputChange = debounce((val) => {
    showFullList.current = false;
    setInputValue(val);
  }, 200);

  const onChange = (val) => {
    if (isDescriptionVisible) {
      setIsDescriptionVisible(false);
    }
    showFullList.current = true;
    setInputValue(val);
    updateGstin(val);
  };

  const getOptions = () => {
    if (showFullList.current) {
      const list: Array<ReactElement> = [];
      if (!gstinList.includes(gstinValue)) {
        list.push(
          <Option key={gstinValue} label={gstinValue} value={gstinValue}>
            <Text size="medium" color="shade.970">
              {gstinValue}
            </Text>
          </Option>,
        );
      }
      list.push(
        gstinList.map((_el) => (
          <Option key={_el} label={_el} value={_el}>
            <Text size="medium" color="shade.970">
              {_el}
            </Text>
          </Option>
        )),
      );
      return list;
    }

    if (!gstinList.includes(gstinValue) && inputValue === gstinValue) {
      return (
        <Option key={gstinValue} label={gstinValue} value={gstinValue}>
          <Text size="medium" color="shade.970">
            {gstinValue}
          </Text>
        </Option>
      );
    }

    const filteredList = gstinList.filter((el) => el.includes(inputValue));
    if (filteredList.length) {
      return filteredList.map((_el) => (
        <Option key={_el} label={_el} value={_el}>
          <Text size="medium" color="shade.970">
            {_el}
          </Text>
        </Option>
      ));
    }

    if (inputValue) {
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
    return null;
  };

  return (
    <Select
      label="GST Identification Number (GSTIN)"
      searchable={true}
      placeholder=""
      inputPlaceholder="Search GSTIN"
      errorText={errorText}
      value={gstinValue}
      helpText={
        isDescriptionVisible
          ? 'Your GSTIN was fetched based on your PAN details, please recheck to avoid any delays in KYC updation and review.'
          : 'Enter GSTIN & get reviewed faster. Should match your business address.'
      }
      onChange={onChange}
      onInputChange={onInputChange}
      disabled={disabled}
      filterOptions={false}
      bottomSheetHeaderText="SELECT GSTIN"
    >
      {getOptions()}
    </Select>
  );
};

export default GstinAutoPopulate;
