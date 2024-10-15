import React from 'react';
import { Box, Checkbox, Text, TextInput, PasswordInput } from '@razorpay/blade/components';

import { FORM_FIELDS } from './constants';
import { useFormikContext } from 'formik';
import { LoginDetailsProps } from './types';

const LoginDetails = ({ partner }: LoginDetailsProps): JSX.Element => {
  const { setFieldValue, values } = useFormikContext();

  const formFields = FORM_FIELDS[partner];

  const onTextChange = ({ name, value }: { name?: string; value?: string }) => {
    if (name && value !== undefined) {
      setFieldValue(name, value);
    }
  };

  const onCheckboxChange = ({ isChecked }: { isChecked: boolean }) => {
    setFieldValue('terms', isChecked);
  };

  return (
    <Box display="flex" flexDirection="column">
      <Text color="surface.text.gray.subtle" marginBottom="spacing.5">
        Enter the following details
      </Text>
      {formFields.map((field) => {
        const { component, label, name, autoCapitalize, placeholder, helpText, content } = field;
        const Input = component === 'TextInput' ? TextInput : PasswordInput;
        if (component === 'TextInput' || component === 'PasswordInput') {
          return (
            <Input
              key={name}
              name={name}
              value={values?.[name]}
              label={label as string}
              placeholder={placeholder}
              marginBottom="spacing.5"
              helpText={helpText}
              autoCapitalize={autoCapitalize}
              onChange={onTextChange}
            />
          );
        }
        return (
          <Checkbox
            size="small"
            key={name}
            name={name}
            onChange={onCheckboxChange}
            value={values?.[name]}
          >
            <Text size="small" color="surface.text.gray.subtle">
              {content}
            </Text>
          </Checkbox>
        );
      })}
    </Box>
  );
};

export default LoginDetails;
