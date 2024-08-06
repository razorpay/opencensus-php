import React from 'react';
import { Box, TextInput, RadioGroup, Radio } from '@razorpay/blade/components';
import { Control, useController } from 'react-hook-form';
import DropdownSelect from 'apps/pos/src/app/components/DropdownSelect/index';
import { SelectDropdownOptions } from 'apps/pos/src/app/types/common';
import { FieldRules } from 'apps/pos/src/app/types/MerchantAdditionalDetails';

type FormFieldProps = {
  key: string;
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
}: FormFieldProps) => {
  const {
    field,
    formState: { errors },
  } = useController({
    name,
    control,
    rules: rules ?? { required: false },
  });

  const getFieldType = (type) => {
    if (!type) return null;
    if (type === 'string') {
      return (
        <TextInput
          necessityIndicator={necessityIndicator}
          isDisabled={isDisabled}
          label={label}
          size="medium"
          onChange={(e) => field.onChange(e.value)}
          value={field.value}
          validationState={errors[name] ? 'error' : 'none'}
          errorText={errorText}
          name={field.name}
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
            if (args) {
              field.onChange(args.values[0]);
            }
          }}
          selectOptions={selectOptions}
          necessityIndicator={necessityIndicator}
          isDisabled={isDisabled}
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
            onChange={({ value }) => field.onChange(value)}
            defaultValue={defaultValue}
          >
            {selectOptions?.map((option) => (
              <Radio key={option.value} value={option.value}>
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
