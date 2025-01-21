import React from 'react';
import { TextInput, TextInputProps } from '@razorpay/blade/components';
import { useField } from 'formik';

const FormikTextInputField = ({ ...props }: TextInputProps) => {
  const formMethods = useField(props.name || '');
  const { setValue } = formMethods[2];
  const meta = formMethods[1];

  const error = meta.touched && meta.error;

  return (
    <TextInput
      onChange={({ value }) => {
        setValue(value);
      }}
      name={props.name}
      value={meta.value}
      validationState={error ? 'error' : 'none'}
      errorText={error || ''}
      {...props}
    />
  );
};

export default FormikTextInputField;
