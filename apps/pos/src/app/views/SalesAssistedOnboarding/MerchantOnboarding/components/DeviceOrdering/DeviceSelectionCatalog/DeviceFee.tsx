import React from 'react';
import { useController, useFormContext } from 'react-hook-form';
import { Box, Heading, Radio, RadioGroup, RupeeIcon, TextInput } from '@razorpay/blade/components';
import {
  AddDeviceToCartForm,
  MODULAR_DEVICE_FIELDS,
  DeviceFee as DeviceFeeType,
} from 'apps/pos/src/app/types/DeviceSelection';
import { DeviceFeeTypes } from 'apps/pos/src/app/constants/DeviceSelection';
import { ONLY_NUMBER_REGEX } from 'apps/pos/src/app/constants/SalesAssistedOnboarding';

interface DeviceFeeProps {
  deviceFee: DeviceFeeType;
}

const DeviceFee = ({ deviceFee }: DeviceFeeProps): JSX.Element => {
  const { control, setValue, watch } = useFormContext<AddDeviceToCartForm>();

  const { field: feeTypeField } = useController({
    name: deviceFee.field,
    control,
    rules: {
      onChange: (field) => {
        const value = field.target?.value;
        if (value !== 'custom') setValue(deviceFee.customAmountField, '');
      },
    },
  });

  const { field: customInputField, fieldState: customInputFieldState } = useController({
    name: deviceFee.customAmountField,
    rules: {
      required: {
        message: 'This field is required',
        value: feeTypeField.value === 'custom',
      },
      pattern: {
        message: 'Enter valid amount',
        value: ONLY_NUMBER_REGEX,
      },
    },
    control,
  });

  const selectedDevicePlan = watch(MODULAR_DEVICE_FIELDS.DEVICE_PLAN);

  return (
    <Box testID={`${deviceFee.field}-device-fee`}>
      <Heading weight="semibold" marginBottom="spacing.5">
        {typeof deviceFee.title === 'function'
          ? deviceFee.title(selectedDevicePlan)
          : deviceFee.title}
      </Heading>
      <RadioGroup
        value={feeTypeField.value}
        onChange={({ value }) => feeTypeField.onChange(value)}
        marginBottom="spacing.5"
      >
        <Box display="flex" alignItems="center">
          {DeviceFeeTypes.map(({ key, name }) => (
            <Radio key={key} value={key} marginRight="spacing.3">
              {name}
            </Radio>
          ))}
        </Box>
        <TextInput
          label=""
          name={customInputField.name}
          value={customInputField.value}
          onChange={({ value = '' }) =>
            setValue(deviceFee.customAmountField, value?.trim() as string)
          }
          leadingIcon={RupeeIcon}
          isDisabled={feeTypeField.value !== 'custom'}
          validationState={customInputFieldState.error ? 'error' : 'none'}
          errorText={customInputFieldState?.error?.message}
        />
      </RadioGroup>
    </Box>
  );
};
export default DeviceFee;
