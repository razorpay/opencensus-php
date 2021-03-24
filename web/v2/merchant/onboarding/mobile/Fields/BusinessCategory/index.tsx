import React, { useState } from 'react';
import Text from '@razorpay/blade/src/atoms/Text';
import Icon from '@razorpay/blade/src/atoms/Icon';
import { Select, GrpOption, Option } from 'v2/components/Select';
import { FormikErrors } from 'formik';
import { debounce } from '../../services/utils';
import useBusinessCategory from '../../hooks/useBusinessCategory';

export interface BusinessCategoryPropsT {
  errorText?: string | false | string[] | FormikErrors<any> | FormikErrors<any>[] | undefined;
  value: string;
  onChange?: (value: string) => void;
  disabled?: boolean;
}

const BusinessCategory: React.FC<BusinessCategoryPropsT> = ({
  value,
  errorText,
  onChange,
  disabled = false,
}) => {
  const [inputValue, setInputValue] = useState('');
  const onInputChange = debounce(setInputValue, 200);
  const [businessCategoriesStatus, businessCategoriesData] = useBusinessCategory(inputValue);
  return (
    <Select
      label="Your Business Category"
      searchable={true}
      placeholder=""
      filterOptions={false}
      inputPlaceholder="Search Business Category"
      errorText={errorText}
      loading={businessCategoriesStatus === 'loading'}
      value={value}
      onInputChange={onInputChange}
      onChange={onChange}
      disabled={disabled}
    >
      {businessCategoriesData
        ? businessCategoriesData.map((item, index) => (
            <GrpOption key={index} label={item.group_name}>
              {item.matches.map((_item, _index) => (
                <Option key={_index} value={_item.subcategory_value} label={_item.subcategory_name}>
                  <Text size="medium" color="shade.970">
                    {_item.subcategory_name}
                  </Text>
                  {_item.tags.length ? (
                    <Text size="small" color="shade.950">
                      includes: {_item.tags.join(', ')}
                    </Text>
                  ) : null}
                </Option>
              ))}
            </GrpOption>
          ))
        : null}
      {businessCategoriesData && businessCategoriesData.length > 0 && value === 'others' ? (
        <GrpOption key="others" label="Others">
          <Option key="1" value="others" label="I am unable to find my business category">
            <Text size="small">I am unable to find my business category</Text>
          </Option>
        </GrpOption>
      ) : null}
      {businessCategoriesData && businessCategoriesData.length === 0 ? (
        <>
          <Option key="0" value="none" label="none" disabled={true}>
            <Text align="center">
              <Icon name="search" />
            </Text>
            <Text align="center" size="small">
              No matching category found. Try another keyword or choose something from the options
              above
            </Text>
          </Option>
          <Option key="1" value="others" label="I am unable to find my business category">
            <Text size="small">I am unable to find my business category</Text>
          </Option>
        </>
      ) : null}
    </Select>
  );
};

export default BusinessCategory;
