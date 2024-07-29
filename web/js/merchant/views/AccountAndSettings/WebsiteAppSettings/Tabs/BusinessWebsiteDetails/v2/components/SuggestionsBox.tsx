import React from 'react';
import { Box, Card, CardBody } from '@razorpay/blade/components';

import { suggestionBoxVariants } from './utils';
import { SuggestionSteps } from '../types';

interface AddWebsiteProps {
  step: SuggestionSteps;
  type: 'ADD_WEBSITE';
}

interface AddMissingAndAdditionalWebsiteProps {
  children?: React.ReactNode;
  type: 'ADD_MISSING' | 'ADD_ADDITIONAL';
}

type SuggestionsBoxProps = AddWebsiteProps | AddMissingAndAdditionalWebsiteProps;

const SuggestionsBox: React.FC<SuggestionsBoxProps> = (props) => {
  const { type, children } = props;
  if (type === 'ADD_MISSING') {
    return (
      <Card
        backgroundColor="surface.background.gray.intense"
        borderRadius="medium"
        elevation="none"
        padding="spacing.5"
        width="100%"
      >
        <CardBody>{children}</CardBody>
      </Card>
    );
  }

  if (type === 'ADD_ADDITIONAL') {
    return (
      <Card
        backgroundColor="surface.background.gray.intense"
        borderRadius="medium"
        elevation="none"
        padding="spacing.5"
      >
        <CardBody>
          <Box display="flex" flexDirection="column" gap="spacing.4">
            {children}
          </Box>
        </CardBody>
      </Card>
    );
  }

  if (type === 'ADD_WEBSITE') {
    const { step } = props;
    return Object.keys(suggestionBoxVariants).includes(step) ? (
      <Card
        backgroundColor="surface.background.gray.intense"
        borderRadius="medium"
        elevation="none"
        padding="spacing.5"
      >
        <CardBody>
          <Box display="flex" flexDirection="column" gap="spacing.4">
            {suggestionBoxVariants[step]}
          </Box>
        </CardBody>
      </Card>
    ) : /* istanbul ignore next */
    null;
  }

  return null;
};

export default SuggestionsBox;
