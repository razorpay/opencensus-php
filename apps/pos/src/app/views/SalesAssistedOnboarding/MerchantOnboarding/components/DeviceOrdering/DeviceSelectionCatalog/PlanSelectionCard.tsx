import React from 'react';
import { useController, useFormContext } from 'react-hook-form';
import { Amount, Box, Card, CardBody, Radio, RadioGroup, Text } from '@razorpay/blade/components';
import { PlanConfig } from 'apps/pos/src/app/types/modular';
import { StyledCard } from 'apps/pos/src/app/components/OnboardingStepCard/styled';
import { MODULAR_DEVICE_FIELDS } from 'apps/pos/src/app/types/DeviceSelection';
import { DevicePlanAvailableCharges } from 'apps/pos/src/app/constants/DeviceSelection';
import { trackEvent, analyticsTypes } from 'apps/pos/src/services/analytics';

interface PlanSelectionCardProps {
  plans: PlanConfig[];
}

const PlanSelectionCard = ({ plans = [] }: PlanSelectionCardProps): JSX.Element | null => {
  const { control } = useFormContext();
  const devicePlan = useController({
    name: MODULAR_DEVICE_FIELDS.DEVICE_PLAN,
    control,
  });

  const onPlanChange = ({ value }) => {
    devicePlan.field.onChange(value);

    trackEvent({
      eventName: analyticsTypes.ANALYTICS_EVENTS.FORM_FIELD,
      action: analyticsTypes.ANALYTICS_ACTIONS.SELECTED,
      properties: {
        formName: 'Device Editing',
        fieldName: 'Plan Selection/Setup Fee',
        fieldType: analyticsTypes.FIELD_TYPES.RADIO,
        label: 'Add Device',
        l1FunnelStage: analyticsTypes.L1_FUNNEL_STAGE.DEVICE_EDITING,
        l2FunnelStage: value,
        section: 'Device Editing',
        subSection: 'Plan Selection/Setup Fee',
      },
    });
  };

  return (
    <RadioGroup
      name={devicePlan.field.name}
      value={devicePlan.field.value}
      defaultValue={devicePlan.formState.defaultValues?.[MODULAR_DEVICE_FIELDS.DEVICE_PLAN]}
      marginBottom="spacing.5"
      onChange={onPlanChange}
      testID="plan-selection-card"
    >
      {plans.map((plan) => (
        <StyledCard key={plan.planName} data-testid={`${plan.planName}-plan-card`}>
          <Card
            padding="spacing.3"
            borderRadius="medium"
            elevation="none"
            isSelected={devicePlan.field.value === plan.planName}
          >
            <CardBody>
              <Box minHeight="80px" display="flex" flexDirection="column" justifyContent="center">
                <Radio
                  value={String(plan.planName)}
                  marginBottom="spacing.3"
                  size="small"
                  testID={`${plan.planName}-plan-card-radio`}
                >
                  {plan.planDisplayName}
                </Radio>
                {DevicePlanAvailableCharges.map(({ key, name }) =>
                  plan?.[key] !== null ? (
                    <Box key={key}>
                      <Box marginBottom="spacing.2" display="flex" justifyContent="space-between">
                        <Text size="small">{name}</Text>
                        <Amount
                          isAffixSubtle={false}
                          suffix="decimals"
                          value={plan?.[key] as number}
                          weight="semibold"
                          size="small"
                        />
                      </Box>
                    </Box>
                  ) : null,
                )}
              </Box>
            </CardBody>
          </Card>
        </StyledCard>
      ))}
    </RadioGroup>
  );
};

export default PlanSelectionCard;
