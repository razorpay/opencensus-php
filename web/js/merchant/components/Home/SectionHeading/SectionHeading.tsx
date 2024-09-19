import { Box, Text, Heading } from '@razorpay/blade/components';
import React from 'react';
import { SectionHeadingStoke } from './styled';

interface SectionHeadingProps {
  caption: string;
  heading: string;
}

function SectionHeading({ caption, heading }: SectionHeadingProps): JSX.Element {
  return (
    <Box>
      <Box display="flex" alignItems="center">
        <Text
          variant="caption"
          weight="regular"
          size="medium"
          color="surface.text.gray.subtle"
          marginRight="spacing.3"
        >
          {caption}
        </Text>
        <SectionHeadingStoke />
      </Box>
      <Heading
        size="large"
        weight="semibold"
        color="surface.text.gray.normal"
        marginTop="spacing.2"
      >
        {heading}
      </Heading>
    </Box>
  );
}

export default SectionHeading;
