import React from 'react';
import { Box, Card, CardBody, Heading, Text, type CardProps } from '@razorpay/blade/components';

type Props = Pick<CardProps, 'onClick'> & {
  icon: React.ReactNode;
  title: React.ReactNode;
  description: React.ReactNode;
};
export const CardButton: React.FC<Props> = ({ icon, title, description, onClick = () => {} }) => {
  return (
    <Card
      elevation="none"
      borderRadius="medium"
      padding="spacing.7"
      backgroundColor="surface.background.gray.moderate"
      onClick={onClick}
    >
      <CardBody>
        <Box display="flex" alignItems="flex-start" gap="spacing.6">
          <Box width="32px">{icon}</Box>
          <Box maxWidth="376px" display="flex" flexDirection="column" gap="spacing.1">
            <Heading size="medium" weight="semibold" color="surface.text.gray.subtle">
              {title}
            </Heading>
            <Text size="medium" weight="regular" color="surface.text.gray.muted">
              {description}
            </Text>
          </Box>
        </Box>
      </CardBody>
    </Card>
  );
};
