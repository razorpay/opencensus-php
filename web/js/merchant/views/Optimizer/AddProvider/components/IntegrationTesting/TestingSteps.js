import React from 'react';
import {
  Box,
  ChevronRightIcon,
  ActionList,
  ActionListItem,
  ActionListItemIcon,
  ActionListItemText,
  CheckIcon,
  Text,
  CloseIcon,
} from '@razorpay/blade/components';

import { StepWrapper } from 'merchant/views/Optimizer/AddProvider/components/styled';

export const TestingSteps = ({ steps }) => {
  const renderStep = (step) => {
    return (
      <StepWrapper active={step.active} key={step.value}>
        <ActionList>
          <ActionListItem
            title=""
            leading={
              step.blocked ? (
                <ActionListItemText>
                  <Text testID="step-blocked-title" color="surface.text.gray.disabled">
                    {step.title}
                  </Text>
                </ActionListItemText>
              ) : step.success && !step.active ? (
                <ActionListItemText>
                  <Text color="feedback.text.positive.intense">{step.title}</Text>
                </ActionListItemText>
              ) : step.failed && !step.active ? (
                <ActionListItemText>
                  <Text color="feedback.text.negative.intense">{step.title}</Text>
                </ActionListItemText>
              ) : (
                <ActionListItemText>
                  <Text>{step.title}</Text>
                </ActionListItemText>
              )
            }
            value={step.value}
            isDisabled={step.blocked}
            trailing={
              <ActionListItemIcon
                icon={() => {
                  if (step.active) {
                    return (
                      <ChevronRightIcon
                        testID="integration-right-icon"
                        color="surface.text.gray.muted"
                        size="medium"
                      />
                    );
                  } else if (step.success) {
                    return (
                      <CheckIcon
                        testID="integration-check-icon"
                        color="feedback.icon.positive.intense"
                        size="medium"
                      />
                    );
                  } else if (step.failed) {
                    return (
                      <CloseIcon
                        testID="integration-close-icon"
                        color="feedback.icon.negative.intense"
                        size="medium"
                      />
                    );
                  }
                  return null;
                }}
              />
            }
          />
        </ActionList>
      </StepWrapper>
    );
  };

  return (
    <Box
      backgroundColor="surface.background.gray.moderate"
      padding={['spacing.7', 'spacing.5']}
      width="260px"
      height="100%"
    >
      <Box
        display="flex"
        flexDirection="column"
        gap="spacing.0"
        padding={['spacing.3', 'spacing.0']}
      >
        {steps?.map(renderStep)}
      </Box>
    </Box>
  );
};
