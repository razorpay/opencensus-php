import React, { useEffect } from 'react';
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
import { useFormik } from 'formik';
import * as Yup from 'yup';
import { isEmpty } from '@razorpay/blade/utils';

export type AllInvitesFiltersType = {
  name: string;
  email: string;
  contact_no: string;
  count: number;
};

const validationSchema = Yup.object().shape({
  name: Yup.string()
    .trim()
    .matches(/^[a-zA-Z\s]+$/, {
      message: 'Name may only contain alphabets and spaces.',
      excludeEmptyString: true,
    })
    .min(4, 'Name should have at least 4 characters.')
    .nullable(),
  email: Yup.string().email('Please enter a valid email id.').nullable(),
  contact_no: Yup.string()
    .trim()
    .length(10, 'Please enter a valid 10-digit mobile number.')
    .nullable(),
});

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
  const formik = useFormik({
    initialValues: initState,
    validationSchema,
    validateOnChange: true,
    onSubmit: handleFormSubmit,
  });

  useEffect(() => {
    const decodedParams = getDecodedParams(location.search);
    const nextVal = { ...initState, ...decodedParams };
    formik.setValues(nextVal);
    if (JSON.stringify(decodedParams) !== '{}') {
      formik.submitForm();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  const handleChange = ({ name, value }: { name?: string; value?: string }) => {
    if (name) {
      formik.setFieldTouched(name);
      formik.setFieldValue(name, value);
    }
  };

  const handleReset = () => {
    history.push({
      search: stringifyQueryParams({}),
      hash: location.hash,
    });
    setPagination({
      skip: 0,
      count: initState.count,
    });
    formik.resetForm();
    onSearch();
  };

  return (
    <FilterContainer>
      <InputContainer>
        <TextInput
          label="Name"
          name="name"
          value={formik.values.name}
          errorText={formik.errors.name}
          validationState={formik.errors.name ? 'error' : 'none'}
          onChange={handleChange}
        />
      </InputContainer>
      <InputContainer>
        <TextInput
          type="email"
          label="Email ID"
          name="email"
          value={formik.values.email}
          errorText={formik.errors.email}
          validationState={formik.errors.email ? 'error' : 'none'}
          onChange={handleChange}
        />
      </InputContainer>
      <InputContainer>
        <TextInput
          label="Phone Number"
          name="contact_no"
          value={formik.values.contact_no}
          errorText={formik.errors.contact_no}
          validationState={formik.errors.contact_no ? 'error' : 'none'}
          onChange={handleChange}
        />
      </InputContainer>
      <InputContainer>
        <TextInput
          label="Count"
          name="count"
          type="number"
          value={String(formik.values.count)}
          errorText={formik.errors.count}
          validationState={formik.errors.count ? 'error' : 'none'}
          onChange={handleChange}
        />
      </InputContainer>
      <ButtonContainer>
        <Link marginTop="spacing.3" htmlTitle="Clear" variant="button" onClick={handleReset}>
          Clear
        </Link>
      </ButtonContainer>
      <ButtonContainer>
        <Button
          variant="tertiary"
          isDisabled={!isEmpty(formik.errors)}
          onClick={() => formik.handleSubmit()}
        >
          Search
        </Button>
      </ButtonContainer>
    </FilterContainer>
  );
};
