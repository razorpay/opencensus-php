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
  onSearch: (id: string, searchParams: string) => void;
  history: History;
  location: Location;
  setPagination: (val: { skip: number; count: number }) => void;
}

const initState = {
  id: '',
  status: '',
  source: '',
  recipient: '',
  count: '25',
};

export const PlatformFeeListFilter = ({
  onSearch,
  location,
  history,
  setPagination,
}: PlatformFilterListProps): JSX.Element => {
  const [formData, setFormData] = useState<PlatformFeeFilters>(initState);

  const handleFormSubmit = (searchData: PlatformFeeFilters) => {
    const { id, ...queryParams } = searchData;
    const searchParams = stringifyQueryParams(encodeSensitiveFields(searchData));
    const apiParams = stringifyQueryParams(encodeSensitiveFields(queryParams));
    history.push({
      pathname: location.pathname,
      hash: location.hash,
      search: searchParams,
    });

    setPagination({
      skip: 0,
      count: Number(searchData.count),
    });
    const params = id
      ? `/${id}?transfer_type=platform${apiParams.replace('?', '&')}`
      : `?transfer_type=platform${apiParams.replace('?', '&')}`;

    onSearch(id, params);
  };

  const getDecodedParams = () => {
    let params = {};
    if (location.search) {
      params = getURLQueryParams(location.search);
    }
    for (const key in params) {
      if (params.hasOwnProperty(key)) {
        params[key] = decodeURI(params[key]);
      }
    }
    return decodeSensitiveFields(params);
  };

  useEffect(() => {
    const decodedParams = getDecodedParams();
    const searchData = { ...formData, ...decodedParams };
    setFormData(searchData);
    if (JSON.stringify(decodedParams) !== '{}') {
      handleFormSubmit(searchData);
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
    setFormData({
      ...initState,
    });
    setPagination({
      skip: 0,
      count: 25,
    });
    onSearch('', '');
  };

  const handleSubmit = () => {
    handleFormSubmit(formData);
  };

  const isSearchAndResetDisabled = JSON.stringify(formData) === JSON.stringify(initState);
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
            value={formData.status}
          />
          <DropdownOverlay>
            <ActionList>
              {statusMenu.map((item, index) => (
                <ActionListItem title={item.title} value={item.value} key={`status-${index}`} />
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
        <Button onClick={handleSubmit} isDisabled={isSearchAndResetDisabled}>
          Search
        </Button>
      </ButtonContainer>
      <ButtonContainer>
        <Button variant="tertiary" onClick={handleReset} isDisabled={isSearchAndResetDisabled}>
          Clear
        </Button>
      </ButtonContainer>
    </FilterContainer>
  );
};
