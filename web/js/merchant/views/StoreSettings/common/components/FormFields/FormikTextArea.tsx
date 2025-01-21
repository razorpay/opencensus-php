import React from 'react';
import { TextArea, TextAreaProps } from '@razorpay/blade/components';
import { useField } from 'formik';

const FormikTextArea = ({ ...props }: TextAreaProps) => {
  const formMethods = useField(props.name || '');
  const { setValue } = formMethods[2];

  const meta = formMethods[1];

  const error = meta.touched && meta.error;
  return (
    <TextArea
      {...props}
      onChange={({ value }) => {
        setValue(value);
      }}
      value={meta.value}
      validationState={error ? 'error' : 'none'}
      errorText={error || ''}
    />
  );
};

export default FormikTextArea;
