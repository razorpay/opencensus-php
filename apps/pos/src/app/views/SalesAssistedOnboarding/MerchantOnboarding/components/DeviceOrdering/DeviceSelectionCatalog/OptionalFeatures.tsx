import React from 'react';
import { Box, Checkbox, TextInput } from '@razorpay/blade/components';
import { useController, useFormContext } from 'react-hook-form';
import {
  DeviceOptionalFeature,
  MODULAR_DEVICE_FIELDS,
} from 'apps/pos/src/app/types/DeviceSelection';
import { ONLY_NUMBER_REGEX } from 'apps/pos/src/app/constants/SalesAssistedOnboarding';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';

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

  const onCheckboxClick = ({ isChecked }) => {
    formField.onChange(isChecked);

    if (isChecked) {
      trackEvent({
        eventName: analyticsTypes.ANALYTICS_EVENTS.FORM_FIELD,
        action: analyticsTypes.ANALYTICS_ACTIONS.SELECTED,
        properties: {
          fieldType: analyticsTypes.FIELD_TYPES.CHECKBOX,
          formName: 'Device Editing',
          fieldName: `${optionalFeature.title}`,
          l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.DEVICE_EDITING,
          l2FunnelStage:
            optionalFeature.field === MODULAR_DEVICE_FIELDS.DEVICE_ADVANCE_RENTAL_FEE
              ? analyticsTypes.L2_FUNNEL_STAGE.RENTAL_ADVANCE
              : analyticsTypes.L2_FUNNEL_STAGE.PURCHASE_PAPER_ROLL,
          section: 'Device Editing',
          subSection:
            optionalFeature.field === MODULAR_DEVICE_FIELDS.DEVICE_ADVANCE_RENTAL_FEE
              ? analyticsTypes.L2_FUNNEL_STAGE.RENTAL_ADVANCE
              : analyticsTypes.L2_FUNNEL_STAGE.PURCHASE_PAPER_ROLL,
        },
      });
    }
  };

  const onTextInputFocus = () => {
    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.FORM_FIELD_FILL,
      action: analyticsTypes.ANALYTICS_ACTIONS.INITIATED,
      properties: {
        fieldType: analyticsTypes.FIELD_TYPES.TEXTBOX,
        formName: 'Device Editing',
        fieldName: `${optionalFeature.title}`,
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.DEVICE_EDITING,
        l2FunnelStage:
          optionalFeature.field === MODULAR_DEVICE_FIELDS.DEVICE_ADVANCE_RENTAL_FEE
            ? analyticsTypes.L2_FUNNEL_STAGE.RENTAL_ADVANCE
            : analyticsTypes.L2_FUNNEL_STAGE.PURCHASE_PAPER_ROLL,
        section: 'Device Editing',
        subSection:
          optionalFeature.field === MODULAR_DEVICE_FIELDS.DEVICE_ADVANCE_RENTAL_FEE
            ? analyticsTypes.L2_FUNNEL_STAGE.RENTAL_ADVANCE
            : analyticsTypes.L2_FUNNEL_STAGE.PURCHASE_PAPER_ROLL,
      },
    });
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
        onChange={onCheckboxClick}
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
            onFocus={onTextInputFocus}
          />
        </Box>
      ) : null}
    </Box>
  );
};

export default OptionalFeatures;
