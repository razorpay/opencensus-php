import React from 'react';
import { RadioGroup, RadioGroupProps } from '@razorpay/blade/components';
import { useField } from 'formik';

const FormikRadioGroup = ({ children, ...props }: RadioGroupProps) => {
  const formMethods = useField(props.name || '');
  const { setValue } = formMethods[2];
  const meta = formMethods[1];

  const error = meta.touched && meta.error;
  return (
    <RadioGroup
      {...props}
      key={meta.value}
      value={meta.value}
      onChange={({ value, ...rest }) => {
        props?.onChange?.({ value, ...rest });
        setValue(value);
      }}
      validationState={error ? 'error' : 'none'}
      errorText={error || ''}
    >
      {children}
    </RadioGroup>
  );
};

export default FormikRadioGroup;
