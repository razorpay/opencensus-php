// core
import React from 'react';
///- core

// components
import {
  Box,
  Alert,
  Spinner,
  Dropdown,
  TextInput,
  ActionList,
  SelectInput,
  ActionListItem,
  DropdownOverlay,
} from '@razorpay/blade/components';
///- components
import { COMMON_Z_INDEX } from 'common/constant';
// constants
import {
  FIELDS_MAPPING,
  BUYER_ADDRESS_FIELDS_CONFIG,
} from 'merchant/views/Transactions/v1/B2bPayments/components/BuyerAddressModal/constants';
///- constants

// helpers
import { handleFieldOnChange } from 'merchant/views/Transactions/v1/B2bPayments/components/BuyerAddressModal/helpers';
///- helpers

// types
import { BuyerAddressFormProps } from 'merchant/views/Transactions/v1/B2bPayments/components/BuyerAddressModal/types';
///- types

const BuyerAddressModal = ({
  states,
  errors,
  values,
  touched,
  countries,
  isStatesLoading,
  isAddressLoading,
  isAddressAlreadyExists,
  onBlur,
  onChange,
  onCountrySelect,
}: BuyerAddressFormProps): JSX.Element => {
  if (isAddressLoading) {
    <Box minHeight="150px" display="flex" justifyContent="center" alignItems="center">
      <Spinner size="xlarge" accessibilityLabel="Checking address" label="Checking address" />
    </Box>;
  }

  return (
    <Box maxWidth="460px">
      {BUYER_ADDRESS_FIELDS_CONFIG.map((fieldConfig) => {
        if (fieldConfig.type === 'text') {
          return (
            <React.Fragment key={fieldConfig.name}>
              <Box marginY="spacing.7">
                <TextInput
                  isRequired={fieldConfig.required}
                  name={fieldConfig.name}
                  value={values[fieldConfig.name]}
                  labelPosition="left"
                  placeholder={fieldConfig.placeholder}
                  errorText={errors[fieldConfig.name]}
                  label={fieldConfig.label}
                  necessityIndicator={fieldConfig.required ? 'required' : 'none'}
                  validationState={
                    errors[fieldConfig.name] && touched[fieldConfig.name] ? 'error' : 'none'
                  }
                  isDisabled={isAddressAlreadyExists}
                  onChange={handleFieldOnChange(fieldConfig, onChange)}
                  onBlur={handleFieldOnChange(fieldConfig, onBlur)}
                />
              </Box>
              {fieldConfig.alert && (
                <Box
                  marginLeft={{
                    base: 'spacing.0',
                    m: '136px',
                  }}
                  marginBottom="spacing.10"
                >
                  <Alert
                    emphasis="subtle"
                    description={fieldConfig.alert}
                    isDismissible={false}
                    color="information"
                  />
                </Box>
              )}
            </React.Fragment>
          );
        }

        if (fieldConfig.type === 'select') {
          if (isAddressAlreadyExists) {
            return (
              <Box key={fieldConfig.name} marginY="spacing.7">
                <TextInput
                  isRequired={fieldConfig.required}
                  name={fieldConfig.name}
                  value={values[fieldConfig.name]}
                  labelPosition="left"
                  errorText={errors[fieldConfig.name]}
                  label={fieldConfig.label}
                  necessityIndicator={fieldConfig.required ? 'required' : 'none'}
                  validationState={
                    errors[fieldConfig.name] && touched[fieldConfig.name] ? 'error' : 'none'
                  }
                  isDisabled={isAddressAlreadyExists}
                  onChange={handleFieldOnChange(fieldConfig, onChange)}
                  onBlur={handleFieldOnChange(fieldConfig, onBlur)}
                />
              </Box>
            );
          } else if (fieldConfig.name === FIELDS_MAPPING.STATE) {
            if (isStatesLoading) {
              return (
                <Box
                  key={fieldConfig.name}
                  marginLeft={{
                    base: 'spacing.0',
                    m: '136px',
                  }}
                  marginBottom="spacing.10"
                >
                  <Spinner accessibilityLabel="Loading states" label="Loading states" />
                </Box>
              );
            }
            return (
              <Box key={fieldConfig.name} marginY="spacing.7">
                <Dropdown selectionType="single" key={values[FIELDS_MAPPING.COUNTRY]}>
                  <SelectInput
                    isRequired={fieldConfig.required}
                    name={fieldConfig.name}
                    labelPosition="left"
                    placeholder={fieldConfig.placeholder}
                    errorText={errors[fieldConfig.name]}
                    label={fieldConfig.label}
                    necessityIndicator={fieldConfig.required ? 'required' : 'none'}
                    validationState={
                      errors[fieldConfig.name] && touched[fieldConfig.name] ? 'error' : 'none'
                    }
                    onChange={handleFieldOnChange(fieldConfig, onChange)}
                    onBlur={handleFieldOnChange(fieldConfig, onBlur)}
                  />
                  <DropdownOverlay zIndex={COMMON_Z_INDEX.DROPDOWN_OVERLAY}>
                    <ActionList>
                      {states.map((state) => (
                        <ActionListItem key={state.value} title={state.label} value={state.value} />
                      ))}
                    </ActionList>
                  </DropdownOverlay>
                </Dropdown>
              </Box>
            );
          }
          return (
            <Box key={fieldConfig.name} marginY="spacing.7">
              <Dropdown selectionType="single">
                <SelectInput
                  isRequired={fieldConfig.required}
                  name={fieldConfig.name}
                  labelPosition="left"
                  placeholder={fieldConfig.placeholder}
                  errorText={errors[fieldConfig.name]}
                  label={fieldConfig.label}
                  necessityIndicator={fieldConfig.required ? 'required' : 'none'}
                  validationState={
                    errors[fieldConfig.name] && touched[fieldConfig.name] ? 'error' : 'none'
                  }
                  onChange={({ name, values }) => {
                    onCountrySelect(values[0]);
                    return handleFieldOnChange(fieldConfig, onChange)({ name, values });
                  }}
                  onBlur={handleFieldOnChange(fieldConfig, onBlur)}
                />
                <DropdownOverlay zIndex={COMMON_Z_INDEX.DROPDOWN_OVERLAY}>
                  <ActionList>
                    {countries.map((country) => (
                      <ActionListItem
                        key={country.code}
                        title={country.name}
                        value={country.code}
                      />
                    ))}
                  </ActionList>
                </DropdownOverlay>
              </Dropdown>
            </Box>
          );
        }

        return null;
      })}
    </Box>
  );
};

export default BuyerAddressModal;
