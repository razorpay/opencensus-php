import React from 'react';
import { Box, Card, CardBody, Text, Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

import { TestimonialCardProps } from './types';

const TestimonialAvatar = styled.img(
  ({ theme }: { theme: Theme }) => `
  width: ${theme.spacing[10]}px;
  height: ${theme.spacing[10]}px;
  border-radius: ${theme.border.radius.max}px;
  object-fit: cover;
  flex-shrink: 0;
`,
);

const TestimonialCard = ({
  avatarSrc,
  name,
  role,
  testimonial,
}: TestimonialCardProps): JSX.Element => (
  <Card padding="spacing.0" marginY="spacing.4">
    <CardBody>
      <Box paddingX="spacing.5" paddingY="spacing.7">
        <Text size="large" color="surface.text.gray.normal">
          {testimonial}
        </Text>
      </Box>
      <Box
        display="flex"
        alignItems="center"
        gap="spacing.5"
        padding="spacing.5"
        borderTopWidth="thin"
        borderTopColor="surface.border.gray.subtle"
      >
        <TestimonialAvatar src={avatarSrc} />
        <Box>
          <Text size="medium" weight="semibold">
            {name}
          </Text>
          <Text size="small" color="surface.text.gray.muted" marginTop="spacing.2">
            {role}
          </Text>
        </Box>
      </Box>
    </CardBody>
  </Card>
);

export default TestimonialCard;
