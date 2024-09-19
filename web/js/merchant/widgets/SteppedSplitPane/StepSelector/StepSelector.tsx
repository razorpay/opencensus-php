import { Box, Text, Badge, Skeleton } from '@razorpay/blade/components';
import React from 'react';
import { StepSelectorButton, StepSelectorHighlight } from '../styled';
import { useSteppedSplitPaneContext } from '../ context';
import { D2C_WIDGET_STEP_TYPES } from 'merchant/components/CrossSellWidget/D2C/constants';

interface Step {
  title: string;
  description: string;
  id: string;
  data: any;
  components: Array<any>;
}

interface StepSelectorProps {
  steps: Array<Step>;
  handleStepSelectorClick: (stepId: string, stepIndex: number, stepTitle: string) => () => void;
  isLoading: boolean;
}

function StepSelector({
  steps,
  handleStepSelectorClick,
  isLoading,
}: StepSelectorProps): JSX.Element {
  const { selectedStepId } = useSteppedSplitPaneContext();

  if (isLoading) {
    return (
      <>
        {Array.from({ length: 5 }).map((_, index) => (
          <StepSelectorButton
            key={index}
            isSelected={false}
            isImmediateNext={false}
            isLoading={true}
          >
            <Box
              display="flex"
              flexDirection="column"
              flex="1"
              height="100%"
              justifyContent="center"
            >
              <Skeleton width="100%" borderRadius="large" height="spacing.5" />
              <Skeleton width="55%" borderRadius="large" height="spacing.5" marginTop="spacing.3" />
            </Box>
          </StepSelectorButton>
        ))}
      </>
    );
  }
  return (
    <>
      {steps.map(({ title, description, id, data, components }, index) => {
        const isStepTypeCoolingPeriodOrValueRealization =
          components[0].type === D2C_WIDGET_STEP_TYPES.D2C_COOLING_PERIOD ||
          components[0].type === D2C_WIDGET_STEP_TYPES.D2C_VALUE_REALIZATION;
        const isSelected = id === selectedStepId;
        const isImmediateNext = selectedStepId && id === selectedStepId + 1;
        const shouldShowStepOptimisedTitle = isSelected && data?.sub_text && data?.value;
        const stepTitle = shouldShowStepOptimisedTitle ? data?.value : title;

        return (
          <StepSelectorButton
            isSelected={isSelected}
            isImmediateNext={isImmediateNext}
            key={id}
            onClick={handleStepSelectorClick(id, index + 1, stepTitle)}
            isLoading={false}
            data-testid={`step-selector-${id}`}
          >
            {isSelected && <StepSelectorHighlight data-testid="selected-step-highlight" />}
            <Box width="100%" textAlign="left">
              {shouldShowStepOptimisedTitle && (
                <Text
                  variant="body"
                  size="medium"
                  textAlign="left"
                  color="feedback.text.positive.intense"
                  weight="semibold"
                  marginBottom="spacing.2"
                >
                  {data?.sub_text}
                </Text>
              )}
              <Text
                variant="body"
                size="medium"
                textAlign="left"
                color={isSelected ? 'surface.text.gray.normal' : 'surface.text.gray.subtle'}
                weight={isSelected ? 'semibold' : 'medium'}
              >
                {stepTitle}
              </Text>
              {!shouldShowStepOptimisedTitle && !isStepTypeCoolingPeriodOrValueRealization && (
                <Badge color="neutral" marginTop="spacing.3">
                  {description}
                </Badge>
              )}
            </Box>
          </StepSelectorButton>
        );
      })}
    </>
  );
}

export default StepSelector;
