import React from 'react';
import { Box, Checkbox, TextInput } from '@razorpay/blade/components';
import { useController, useFormContext } from 'react-hook-form';
import { DeviceOptionalFeature } from 'apps/pos/src/app/types/DeviceSelection';
import { ONLY_NUMBER_REGEX } from 'apps/pos/src/app/constants/SalesAssistedOnboarding';

interface OptionalFeaturesProps {
  optionalFeature: DeviceOptionalFeature;
}

const OptionalFeatures = ({ optionalFeature }: OptionalFeaturesProps): JSX.Element => {
  const { control, setValue } = useFormContext();
  const { field: formField } = useController({
    name: optionalFeature.field,
    control,
    rules: {
      onChange: (field) => {
        if (!field.target.value && optionalFeature.customInputField) {
          setValue(optionalFeature.customInputField, '');
        }
      },
    },
  });

  const { field: customInputField, fieldState: customInputFieldState } = useController({
    name: optionalFeature.customInputField as string,
    rules: {
      required: {
        message: 'This field is required',
        value: !!formField.value,
      },
      pattern: {
        message: 'Enter valid number',
        value: ONLY_NUMBER_REGEX,
      },
    },
    control,
  });

  const handleOnCustomInputChange = ({
    name,
    value,
  }: {
    name: string;
    value: string | undefined;
  }) => {
    if (isNaN(Number(value))) return;
    setValue(name, Number(value));
  };

  return (
    <Box
      display="flex"
      justifyContent="space-between"
      marginBottom="spacing.5"
      testID={`${optionalFeature.title}-optional-field`}
    >
      <Checkbox
        value={optionalFeature.field}
        onChange={({ isChecked }) => formField.onChange(isChecked)}
        isChecked={formField.value}
      >
        {optionalFeature.title}
      </Checkbox>
      {optionalFeature.customInputField ? (
        <Box maxWidth="80px">
          <TextInput
            label=""
            name={customInputField.name}
            value={customInputField.value}
            onChange={({ name, value = '' }) =>
              handleOnCustomInputChange({ name: name as string, value: value.trim() })
            }
            isDisabled={!formField.value}
            validationState={customInputFieldState.error ? 'error' : 'none'}
            errorText={customInputFieldState?.error?.message}
          />
        </Box>
      ) : null}
    </Box>
  );
};

export default OptionalFeatures;
