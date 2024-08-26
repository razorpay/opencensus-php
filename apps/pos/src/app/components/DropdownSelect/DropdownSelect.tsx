import React from 'react';
import {
  ActionList,
  ActionListItem,
  BottomSheet,
  BottomSheetBody,
  BottomSheetHeader,
  Dropdown,
  DropdownOverlay,
  SelectInput,
} from '@razorpay/blade/components';
import { ControllerRenderProps } from 'react-hook-form';
import { SelectDropdownOptions } from 'apps/pos/src/app/types/common';
import { useScreen } from 'apps/pos/src/app/utils/hooks/useScreen';
import { FieldRules } from 'apps/pos/src/app/types/MerchantAdditionalDetails';

interface DropdownSelectProps {
  field: ControllerRenderProps;
  label: string;
  name: string;
  value: any;
  validationState: 'none' | 'error' | 'success' | undefined;
  errorText: string;
  rules?: FieldRules;
  onChange: (args) => void;
  selectOptions: Array<SelectDropdownOptions>;
  necessityIndicator: 'required' | 'none' | 'optional' | undefined;
  isDisabled: boolean;
}
const DropdownSelect = ({
  label,
  name,
  value,
  field,
  selectOptions,
  errorText = 'Required',
  rules,
  necessityIndicator,
  validationState,
  isDisabled,
}: DropdownSelectProps) => {
  const { isMobile } = useScreen();
  const renderBody = (selectOptions) => {
    return (
      <ActionList>
        {!rules?.required ? <ActionListItem title="None" value="" /> : null}
        {selectOptions?.map((option: SelectDropdownOptions) => (
          <ActionListItem key={option.value} title={option.label} value={option.value} />
        ))}
      </ActionList>
    );
  };

  return (
    <Dropdown selectionType="single">
      <SelectInput
        isDisabled={isDisabled}
        necessityIndicator={necessityIndicator}
        label={label}
        name={name}
        value={value}
        validationState={validationState}
        errorText={errorText}
        onChange={(args) => {
          if (args) {
            field.onChange(args.values[0]);
          }
        }}
      />
      {isMobile ? (
        <BottomSheet snapPoints={[0.5, 0.8, 1]}>
          <BottomSheetHeader title={label} />
          <BottomSheetBody>{renderBody(selectOptions)}</BottomSheetBody>
        </BottomSheet>
      ) : (
        <DropdownOverlay>{renderBody(selectOptions)}</DropdownOverlay>
      )}
    </Dropdown>
  );
};

export default DropdownSelect;
