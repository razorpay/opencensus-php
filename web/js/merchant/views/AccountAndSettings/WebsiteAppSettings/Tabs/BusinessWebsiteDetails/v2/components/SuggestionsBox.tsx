import React from 'react';
import { Box, Card, CardBody } from '@razorpay/blade/components';

import { suggestionBoxVariants } from './utils';
import { SuggestionSteps } from '../types';

interface SuggestionsBoxProps {
  step: SuggestionSteps;
}
const SuggestionsBox: React.FC<SuggestionsBoxProps> = ({ step }) => {
  return Object.keys(suggestionBoxVariants).includes(step) ? (
    <Card
      backgroundColor="surface.background.gray.intense"
      borderRadius="medium"
      elevation="none"
      padding="spacing.7"
    >
      <CardBody>
        <Box display="flex" flexDirection="column" gap="spacing.4">
          {suggestionBoxVariants[step]}
        </Box>
      </CardBody>
    </Card>
  ) : /* istanbul ignore next */
  null;
};

export default SuggestionsBox;
