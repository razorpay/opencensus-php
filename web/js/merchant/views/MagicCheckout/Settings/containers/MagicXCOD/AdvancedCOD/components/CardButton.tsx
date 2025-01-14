import React from 'react';
import { Box, Card, CardBody, Heading, Text, type CardProps } from '@razorpay/blade/components';

type Props = Required<Pick<CardProps, 'onClick' | 'accessibilityLabel'>> & {
  icon: React.ReactNode;
  title: React.ReactNode;
  description: React.ReactNode;
  disabled?: boolean;
};
export const CardButton: React.FC<Props> = ({
  icon,
  title,
  description,
  accessibilityLabel,
  onClick = () => {},
  disabled = false,
}) => {
  return (
    <Card
      elevation="none"
      borderRadius="medium"
      padding="spacing.7"
      onClick={!disabled ? onClick : undefined}
      accessibilityLabel={accessibilityLabel}
    >
      <CardBody>
        <Box
          display="flex"
          alignItems="flex-start"
          gap="spacing.6"
          opacity={disabled ? '0.7' : '1'}
        >
          <Box
            width="48px"
            height="48px"
            borderRadius="max"
            display="flex"
            alignItems="center"
            justifyContent="center"
            backgroundColor="surface.background.sea.subtle"
          >
            {icon}
          </Box>
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
