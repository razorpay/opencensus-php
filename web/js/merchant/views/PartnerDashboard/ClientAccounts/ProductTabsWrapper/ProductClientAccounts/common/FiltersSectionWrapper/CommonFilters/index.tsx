import React from 'react';
import { BoxProps, TextInput, TextInputProps } from '@razorpay/blade/components';
import * as Yup from 'yup';

import { PaginationParamsType, User } from 'common/typings';
import { ListFiltersContextType } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper/context';
import { InputContainer } from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper/styles';
import useListFilters from 'merchant/views/PartnerDashboard/ClientAccounts/ProductTabsWrapper/ProductClientAccounts/common/FiltersSectionWrapper/useListFilters';

import ActivationStatusFilter from './ActivationStatusFilter';

export const commonValidations = {
  idValidation: Yup.string()
    .trim()
    .matches(/^[a-zA-Z0-9]+$/, {
      message: 'The id may only contain letters and numbers.',
      excludeEmptyString: true,
    }),
  nameValidation: Yup.string()
    .trim()
    .matches(/^[a-zA-Z\s]+$/, {
      message: 'Name may only contain alphabets and spaces.',
      excludeEmptyString: true,
    })
    .min(4, 'Name should have at least 4 characters.')
    .nullable(),
  emailValidation: Yup.string().email('Please a valid email id.').nullable(),
  mobileValidation: Yup.string()
    .trim()
    .length(10, 'Please a valid 10-digit mobile number.')
    .nullable(),
};

export type ListFilterConfig = {
  fieldLabel?: string;
  fieldName: string;
  fieldPlaceholder?: string;
  fieldType: TextInputProps['type'] | 'custom';
  fieldWidth?: BoxProps['flexBasis'];
  Component?: React.ComponentType<{
    value: string;
    errorText: string;
    onChange: ListFiltersContextType['handleChange'];
  }>;
};

export const commonFilterInputs: Record<string, ListFilterConfig> = {
  accountIdField: {
    fieldName: 'id',
    fieldLabel: 'Account ID',
    fieldPlaceholder: "Merchant's ID",
    fieldType: 'text',
  },
  nameField: {
    fieldName: 'name',
    fieldLabel: 'Name',
    fieldPlaceholder: "Merchant's Name",
    fieldType: 'text',
  },
  accountNameField: {
    fieldName: 'name',
    fieldLabel: 'Account Name',
    fieldPlaceholder: "Merchant's Name",
    fieldType: 'text',
  },
  emailField: {
    fieldType: 'email',
    fieldLabel: 'Email ID',
    fieldPlaceholder: 'Email ID',
    fieldName: 'email',
  },
  appIdField: {
    fieldType: 'text',
    fieldLabel: 'Application ID',
    fieldPlaceholder: 'Application ID',
    fieldName: 'application_id',
  },
  phoneNumberField: {
    fieldType: 'text',
    fieldLabel: 'Phone Number',
    fieldPlaceholder: 'Phone Number',
    fieldName: 'contact_no',
  },
  contactField: {
    fieldType: 'text',
    fieldLabel: 'Contact',
    fieldPlaceholder: 'Contact number',
    fieldName: 'contact_no',
  },
  contactMobileField: {
    fieldType: 'text',
    fieldLabel: 'Phone Number',
    fieldPlaceholder: 'Phone Number',
    fieldName: 'contact_mobile',
  },
  contactInfoField: {
    fieldType: 'text',
    fieldLabel: 'Contact',
    fieldPlaceholder: 'Email/Contact Number',
    fieldName: 'contact_info',
  },
  countField: {
    fieldType: 'number',
    fieldLabel: 'Count',
    fieldName: 'count',
    fieldWidth: '70px',
  },
  activationStatusField: {
    fieldType: 'custom',
    fieldName: 'activation_status',
    fieldWidth: '160px',
    Component: ActivationStatusFilter,
  },
};
export type RenderFiltersSectionProps = {
  user: User;
  paginationState: PaginationParamsType;
  setPagination: (args: PaginationParamsType) => void;
  refetch: () => void;
};

type CommonFiltersProps = {
  filtersList: Array<ListFilterConfig>;
};
const CommonFilters = ({ filtersList }: CommonFiltersProps): JSX.Element => {
  const { formik, handleChange } = useListFilters();
  const { values = {}, errors = {} } = formik || {};
  return (
    <>
      {filtersList.map(
        ({ fieldName, fieldLabel, fieldWidth, fieldType, fieldPlaceholder, Component }) =>
          fieldType === 'custom' && Component ? (
            <InputContainer flexBasis={fieldWidth} key={fieldName}>
              <Component
                value={String(values[fieldName])}
                errorText={errors[fieldName] as string}
                onChange={handleChange}
              />
            </InputContainer>
          ) : (
            <InputContainer flexBasis={fieldWidth} key={fieldName}>
              <TextInput
                type={fieldType as TextInputProps['type']}
                label={fieldLabel as string}
                name={fieldName}
                placeholder={fieldPlaceholder}
                value={String(values[fieldName])}
                errorText={errors[fieldName] as string}
                validationState={errors[fieldName] ? 'error' : 'none'}
                onChange={handleChange}
              />
            </InputContainer>
          ),
      )}
    </>
  );
};
export default CommonFilters;
