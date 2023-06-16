import React, { useState, useEffect } from 'react';
import type { History, Location } from 'history';
import { TextInput, Button, Link } from '@razorpay/blade/components';
import {
  decodeSensitiveFields,
  encodeSensitiveFields,
  getURLQueryParams,
  stringifyQueryParams,
} from 'common/utils/rzp-utils';
import { FilterContainer, InputContainer, ButtonContainer } from './styles';
import { PaginationParamsType } from 'common/typings';

export type AllInvitesFiltersType = {
  name: string;
  email: string;
  contact_no: string;
  count: number;
};

export const getDecodedParams = (search: string = location.search): Record<string, string> => {
  let params = {};
  if (search) {
    params = getURLQueryParams(search);
  }

  for (const key in params) {
    if (params.hasOwnProperty(key)) {
      params[key] = decodeURI(params[key]);
      if (key === 'count' || key === 'skip') params[key] = Number(params[key]);
    }
  }

  return decodeSensitiveFields(params);
};

interface AllInvitesFilterProps {
  count: number;
  onSearch: () => void;
  history: History;
  location: Location;
  setPagination: (val: PaginationParamsType) => void;
}

const initState: AllInvitesFiltersType = {
  name: '',
  email: '',
  contact_no: '',
  count: 25,
};

export const AllInvitesFilter = ({
  onSearch,
  count,
  location,
  history,
  setPagination,
}: AllInvitesFilterProps): JSX.Element => {
  const [formData, setFormData] = useState<AllInvitesFiltersType>(initState);

  const handleFormSubmit = (formData) => {
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
    onSearch();
  };

  useEffect(() => {
    const decodedParams = getDecodedParams(location.search);
    setFormData((prevVal) => {
      const nextVal = { ...prevVal, ...decodedParams };

      if (JSON.stringify(decodedParams) !== '{}') {
        handleFormSubmit(nextVal);
      }
      return nextVal;
    });
    // eslint-disable-next-line react-hooks/exhaustive-deps
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
    onSearch();
  };

  return (
    <FilterContainer>
      <InputContainer>
        <TextInput label="Name" name="name" value={formData.name} onChange={handleChange} />
      </InputContainer>
      <InputContainer>
        <TextInput label="Email ID" name="email" value={formData.email} onChange={handleChange} />
      </InputContainer>
      <InputContainer>
        <TextInput
          label="Phone Number"
          name="contact_no"
          value={formData.contact_no}
          onChange={handleChange}
        />
      </InputContainer>
      <InputContainer>
        <TextInput
          label="Count"
          name="count"
          type="number"
          value={String(formData.count)}
          onChange={handleChange}
        />
      </InputContainer>
      <ButtonContainer>
        <Link marginBottom="spacing.3" htmlTitle="Clear" variant="button" onClick={handleReset}>
          Clear
        </Link>
      </ButtonContainer>
      <ButtonContainer>
        <Button variant="tertiary" onClick={() => handleFormSubmit(formData)}>
          Search
        </Button>
      </ButtonContainer>
    </FilterContainer>
  );
};
