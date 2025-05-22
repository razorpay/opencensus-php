/* eslint-disable consistent-return */
import React from 'react';
import { TextInput, TextArea, Box } from '@razorpay/blade/components';
import { isValidNumber } from 'merchant/views/GCMS/shared/utils';
import { MODE } from 'merchant/views/GCMS/Programs/CreateProgram/constants';

type DetailsComponentProps = {
  values: object;
  errors: object;
  touched: { [key: string]: boolean };
  onChange: (key: string, val: any) => void;
  editMode: string;
};

export default function Details({
  values,
  errors,
  touched,
  onChange,
  editMode,
}: DetailsComponentProps) {
  function onDiscountChange({ name, value }) {
    if (isValidNumber(value)) {
      onChange(name, value);
    }
  }

  return (
    <Box display="flex" flexDirection="column" gap="spacing.4">
      <TextInput
        isRequired
        necessityIndicator="required"
        autoFocus
        label="Program Name"
        labelPosition="top"
        name="name"
        placeholder="Enter a unique name for your program"
        maxCharacters={50}
        onChange={({ name, value }) => {
          onChange(name, value);
        }}
        value={values.name}
        validationState={touched.name && errors.name ? 'error' : 'none'}
        errorText={errors?.name}
        testID="program-name"
      />

      <TextArea
        isRequired
        necessityIndicator="required"
        label="Program Description"
        labelPosition="top"
        name="description"
        placeholder="Add a short description of your program"
        maxCharacters={250}
        validationState={touched.description && errors?.description ? 'error' : 'none'}
        errorText={errors?.description}
        onChange={({ name, value }) => {
          onChange(name, value);
        }}
        value={values.description}
      />

      <TextInput
        isRequired
        necessityIndicator="required"
        label="Discount"
        type="number"
        name="discount"
        placeholder="0"
        helpText="Specify the percentage discount offered. This cannot be modified once the program is created."
        labelPosition="top"
        validationState={touched.discount && errors?.discount ? 'error' : 'none'}
        errorText={errors?.discount}
        onChange={onDiscountChange}
        value={values.discount}
        showClearButton={true}
        isDisabled={editMode === MODE.EDIT}
        onClearButtonClick={() => onChange('discount', '')}
        suffix="%"
      />
    </Box>
  );
}
