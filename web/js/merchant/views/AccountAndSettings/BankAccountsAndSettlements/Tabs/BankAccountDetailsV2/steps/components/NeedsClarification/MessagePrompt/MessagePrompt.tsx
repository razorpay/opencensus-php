import { Text } from '@razorpay/blade/components';
import React from 'react';
import { DescriptionContent, StyledMessageIcon, StyledPrompt } from './styled';

const MessagePrompt = ({
  title,
  messageBody,
}: {
  title: string;
  messageBody: string;
}): JSX.Element => {
  return (
    <StyledPrompt>
      <StyledMessageIcon>
        <i className="i i-prompt-message" />
      </StyledMessageIcon>
      <DescriptionContent>
        <Text size="medium" variant="body" weight="semibold">
          {title}
        </Text>
        <Text size="small" variant="body">
          “{messageBody}”
        </Text>
      </DescriptionContent>
    </StyledPrompt>
  );
};

export default MessagePrompt;
