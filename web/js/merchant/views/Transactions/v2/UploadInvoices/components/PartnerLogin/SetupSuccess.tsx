import React from 'react';
import {
  Box,
  Text,
  Collapsible,
  CollapsibleLink,
  CollapsibleBody,
} from '@razorpay/blade/components';
import { getOnboardingStepData } from './utils';
import { SetupSuccessProps } from './types';

const SetupSuccess = ({ partner, status }: SetupSuccessProps): JSX.Element => {
  const { image, title, description, question, answer } = getOnboardingStepData(partner, status);

  return (
    <Box display="flex" flexDirection="column">
      <Box height="158px" maxHeight="158px" width="100%" marginBottom="spacing.7">
        <img height="100%" src={image} />
      </Box>
      <Text marginBottom="spacing.5" size="small">
        {title}
      </Text>
      <Text marginBottom="spacing.5" size="small">
        {description}
      </Text>
      {question && answer && (
        <Collapsible>
          <CollapsibleLink size="small">{question}</CollapsibleLink>
          <CollapsibleBody>
            <Text size="small">{answer}</Text>
          </CollapsibleBody>
        </Collapsible>
      )}
    </Box>
  );
};

export default SetupSuccess;
