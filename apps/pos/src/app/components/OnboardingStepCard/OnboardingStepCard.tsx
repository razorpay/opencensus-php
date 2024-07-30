import React, { ReactElement } from 'react';
import { Box, Card, CardBody, Text } from '@razorpay/blade/components';
import StatusBadge from '../StatusBadge';
import { StyledCard } from './styled';

interface OnboardingStepCardProps {
  slug: string;
  title: string;
  description: string;
  icon: ReactElement;
  status: string;
  isDisabled: boolean;
  onClick?: () => void;
}

const OnboardingStepCard = ({
  title,
  description,
  icon,
  status,
  isDisabled,
  onClick,
}: OnboardingStepCardProps): JSX.Element => {
  const iconComponent = React.cloneElement(icon, {
    size: 'large',
    color: isDisabled ? 'interactive.icon.primary.disabled' : 'surface.icon.onCloud.onSubtle',
  });

  return (
    <StyledCard isDisabled={isDisabled} data-testid="onboarding-step-card">
      <Card
        padding="spacing.5"
        marginBottom="spacing.5"
        onClick={isDisabled ? () => null : onClick}
        isSelected={false}
        elevation={isDisabled ? 'none' : 'lowRaised'}
      >
        <CardBody>
          <Box
            display={{ base: 'block', l: 'flex' }}
            position="relative"
            justifyContent="space-between"
            alignItems="center"
          >
            <Box display={{ base: 'block', l: 'flex' }} alignItems="center">
              <Box
                padding="spacing.2"
                backgroundColor="surface.background.primary.subtle"
                marginRight="spacing.5"
                borderRadius="medium"
                height={{ base: '44px', l: '55px' }}
                width={{ base: '44px', l: '55px' }}
                display="flex"
                alignItems="center"
                justifyContent="center"
                marginBottom={{ base: 'spacing.5', l: 'spacing.0' }}
              >
                {iconComponent}
              </Box>
              <Box>
                <Text
                  size="large"
                  marginBottom={{ base: 'spacing.3', l: 'spacing.0' }}
                  color={isDisabled ? 'surface.text.gray.disabled' : 'surface.text.gray.normal'}
                  weight="semibold"
                >
                  {title}
                </Text>
                <Text
                  size="small"
                  color={isDisabled ? 'surface.text.gray.disabled' : 'surface.text.gray.muted'}
                >
                  {description}
                </Text>
              </Box>
            </Box>
            {!isDisabled ? (
              <Box position={{ base: 'absolute', l: 'static' }} top="0px" right="0px">
                <StatusBadge type={status} size="medium" />
              </Box>
            ) : null}
          </Box>
        </CardBody>
      </Card>
    </StyledCard>
  );
};

export default OnboardingStepCard;
