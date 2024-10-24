import React from 'react';
import { Box, TextInput, RadioGroup, Radio } from '@razorpay/blade/components';
import { Control, useController, ControllerRenderProps, FieldValues } from 'react-hook-form';
import DropdownSelect from 'apps/pos/src/app/components/DropdownSelect/index';
import { SelectDropdownOptions } from 'apps/pos/src/app/types/common';
import { FieldRules } from 'apps/pos/src/app/types/MerchantAdditionalDetails';

type FormFieldProps = {
  testID?: string;
  key?: string;
  type: string;
  label: string;
  necessityIndicator: 'required' | 'none' | 'optional' | undefined;
  control: Control;
  name: string;
  errorText: string;
  rules?: FieldRules;
  selectOptions?: Array<SelectDropdownOptions>;
  defaultValue: string;
  isDisabled: boolean;
  onBottomSheetDismissCallback?: () => void;
  onTextInputClick?: (field: ControllerRenderProps<FieldValues, string>) => void;
  onRadioBtnChangeCallback?: (field: ControllerRenderProps<FieldValues, string>) => void;
  onDropdownChangeCallback?: (args) => void;
};

const FormField = ({
  type,
  name,
  rules,
  control,
  selectOptions = [],
  defaultValue,
  label,
  errorText = 'Required',
  necessityIndicator,
  isDisabled,
  onBottomSheetDismissCallback,
  onTextInputClick,
  onRadioBtnChangeCallback,
  onDropdownChangeCallback,
  testID,
}: FormFieldProps) => {
  const {
    field,
    formState: { errors },
  } = useController({
    name,
    control,
    rules: rules ?? { required: false },
  });

  const onTextInputFocus = () => {
    onTextInputClick?.(field);
  };

  const onRadioBtnChange = (value) => {
    field.onChange(value);
    onRadioBtnChangeCallback?.(field);
  };

  const onDropdownChange = (args) => {
    onDropdownChangeCallback?.(args);
  };

  const getFieldType = (type) => {
    if (!type) return null;
    if (type === 'string') {
      return (
        <TextInput
          testID={testID}
          ref={field.ref}
          necessityIndicator={necessityIndicator}
          isDisabled={isDisabled}
          label={label}
          size="medium"
          onChange={(e) => field.onChange(e.value)}
          value={field.value}
          validationState={errors[name] ? 'error' : 'none'}
          errorText={errorText}
          isRequired={rules?.required}
          name={field.name}
          onFocus={onTextInputFocus}
        />
      );
    }
    if (type === 'select') {
      return (
        <DropdownSelect
          field={field}
          label={label}
          name={field.name}
          value={field.value}
          validationState={errors?.[name] ? 'error' : 'none'}
          errorText={errorText}
          rules={rules}
          onChange={(args) => {
            onDropdownChange(args);
          }}
          selectOptions={selectOptions}
          necessityIndicator={necessityIndicator}
          isDisabled={isDisabled}
          onBottomSheetDismissCallback={onBottomSheetDismissCallback}
          testID={testID}
        />
      );
    }
    if (type === 'radio') {
      return (
        <Box width="fit-content">
          <RadioGroup
            necessityIndicator={necessityIndicator}
            isDisabled={isDisabled}
            label={label}
            name={field.name}
            onChange={({ value }) => {
              onRadioBtnChange(value);
            }}
            defaultValue={defaultValue}
            isRequired={rules?.required}
          >
            {selectOptions?.map((option) => (
              <Radio ref={field.ref} key={option.value} value={option.value}>
                {option.label}
              </Radio>
            ))}
          </RadioGroup>
        </Box>
      );
    }
    return null;
  };
  return <Box>{getFieldType(type)}</Box>;
};

export default FormField;
