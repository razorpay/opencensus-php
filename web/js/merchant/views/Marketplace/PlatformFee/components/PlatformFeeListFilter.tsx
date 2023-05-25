import React, { useState, useEffect } from 'react';
import type { History, Location } from 'history';
import {
  TextInput,
  Dropdown,
  SelectInput,
  DropdownOverlay,
  ActionList,
  ActionListItem,
  Button,
} from '@razorpay/blade/components';
import {
  stringifyQueryParams,
  getURLQueryParams,
  encodeSensitiveFields,
  decodeSensitiveFields,
} from 'common/utils/rzp-utils';
import {
  FilterContainer,
  InputContainer,
  ButtonContainer,
} from 'merchant/views/Marketplace/PlatformFee/components/styles';
import { statusMenu } from 'merchant/views/Marketplace/PlatformFee/constants';

type PlatformFeeFilters = {
  id: string;
  status: string;
  source: string;
  recipient: string;
  count: string;
};

interface PlatformFilterListProps {
  count: number;
  onSearch: (searchParams: string) => void;
  history: History;
  location: Location;
  setPagination: (val: { skip: number; count: number }) => void;
}

// temp logic to reset Blade dropdown component after formReset. will update the logic once blade provides method to reset
const useForceRerender = () => {
  const [rerenderTargetKey, setRerenderTargetKey] = useState(1);
  const forceRerenderTarget = () => {
    setRerenderTargetKey(rerenderTargetKey + 1);
  };
  return { rerenderTargetKey, forceRerenderTarget };
};

// Blade dropdown takes default value from initial render so using this method to check params in initial render.
const getDecodedParams = (locationProp = location) => {
  let params = {};
  if (locationProp.search) {
    params = getURLQueryParams(locationProp.search);
  }

  for (const key in params) {
    if (params.hasOwnProperty(key)) {
      params[key] = decodeURI(params[key]);
    }
  }
  return decodeSensitiveFields(params);
};

const initState = {
  id: '',
  status: getDecodedParams().status,
  source: '',
  recipient: '',
  count: getDecodedParams().count || '25',
};

export const PlatformFeeListFilter = ({
  onSearch,
  count,
  location,
  history,
  setPagination,
}: PlatformFilterListProps): JSX.Element => {
  const [formData, setFormData] = useState<PlatformFeeFilters>(initState);
  const { rerenderTargetKey, forceRerenderTarget } = useForceRerender();

  const handleFormSubmit = () => {
    const searchParams = stringifyQueryParams(encodeSensitiveFields(formData));
    history.push({
      pathname: location.pathname,
      hash: location.hash,
      search: searchParams,
    });
    if (count !== Number(formData.count)) {
      setPagination({
        skip: 0,
        count: Number(formData.count),
      });
    }
    onSearch(searchParams);
  };

  useEffect(() => {
    const decodedParams = getDecodedParams(location);
    setFormData((prevVal) => {
      return { ...prevVal, ...decodedParams };
    });
    if (JSON.stringify(decodedParams) !== '{}') {
      forceRerenderTarget();
      handleFormSubmit();
    }
  }, []);

  const handleChange = (event) => {
    const { name, value } = event;
    setFormData((prevValue) => {
      return { ...prevValue, [name]: value };
    });
  };

  const handleReset = () => {
    history.push({
      search: stringifyQueryParams({}),
      hash: location.hash,
    });
    forceRerenderTarget();
    setFormData({
      ...initState,
    });
    setPagination({
      skip: 0,
      count: 25,
    });
    onSearch('');
  };

  return (
    <FilterContainer>
      <InputContainer>
        <TextInput
          label="Platform Fee ID"
          name="id"
          placeholder="Enter Platform Fee ID"
          value={formData.id}
          onChange={handleChange}
        />
      </InputContainer>
      <InputContainer>
        <TextInput
          label="Payment ID"
          name="source"
          value={formData.source}
          placeholder="Enter Payment ID"
          onChange={handleChange}
        />
      </InputContainer>
      <InputContainer>
        <Dropdown selectionType="single">
          <SelectInput
            label="Status"
            labelPosition="top"
            name="status"
            onChange={({ name, values }) => {
              handleChange({ name, value: values[0] });
            }}
            placeholder="Select Option"
            validationState="none"
          />
          <DropdownOverlay>
            <ActionList key={rerenderTargetKey}>
              {statusMenu.map((item, index) => (
                <ActionListItem
                  title={item.title}
                  value={item.value}
                  key={`status-${index}`}
                  isDefaultSelected={formData.status === item.value}
                />
              ))}
            </ActionList>
          </DropdownOverlay>
        </Dropdown>
      </InputContainer>
      <InputContainer>
        <TextInput
          label="Recipient ID"
          name="recipient"
          placeholder="Enter Recipient ID"
          value={formData.recipient}
          onChange={handleChange}
        />
      </InputContainer>
      <InputContainer>
        <TextInput
          label="Count"
          name="count"
          placeholder="Enter Count"
          value={formData.count}
          onChange={handleChange}
        />
      </InputContainer>
      <ButtonContainer>
        <Button onClick={handleFormSubmit}>Search</Button>
      </ButtonContainer>
      <ButtonContainer>
        <Button variant="tertiary" onClick={handleReset}>
          Clear
        </Button>
      </ButtonContainer>
    </FilterContainer>
  );
};
