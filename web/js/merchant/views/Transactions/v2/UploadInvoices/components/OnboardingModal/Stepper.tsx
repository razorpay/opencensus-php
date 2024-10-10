import React from 'react';
import {
  StepGroup,
  StepItem,
  StepItemIcon,
  CheckIcon,
  Text,
  Box,
} from '@razorpay/blade/components';

import { StepperProps } from './types';

const Stepper = ({ data, step }: StepperProps): JSX.Element => (
  <Box>
    <Text size="large" color="surface.text.gray.subtle" weight="semibold">
      Please complete the following steps 👇
    </Text>
    <StepGroup orientation="vertical" size="medium">
      {data.map(({ description }, index) => (
        <StepItem
          key={index}
          isSelected={step == index}
          title={`Step ${index + 1}`}
          stepProgress={step >= index ? 'full' : 'none'}
          onClick={() => {}}
          marker={<StepItemIcon icon={CheckIcon} color={step >= index ? 'positive' : 'neutral'} />}
        >
          <Text size="medium">{description}</Text>
        </StepItem>
      ))}
    </StepGroup>
  </Box>
);

export default Stepper;
