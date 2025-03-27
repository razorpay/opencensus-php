import React, { ReactElement } from 'react';
import { Box, Card, CardBody, Text } from '@razorpay/blade/components';
import StatusBadge from 'apps/pos/src/app/components/StatusBadge/StatusBadge';
import { StyledCard } from './styled';
import { PricingNcStatus } from 'apps/pos/src/app/types/PaymentsAndService';
import { AllBadgeTypes } from 'apps/pos/src/app/types/common';

interface OnboardingStepCardProps {
  slug: string;
  title: string;
  description: string;
  icon: ReactElement;
  status: AllBadgeTypes;
  isDisabled: boolean;
  pricingNcStatus?: PricingNcStatus | null;
  onClick?: () => void;
}

const OnboardingStepCard = ({
  title,
  description,
  icon,
  status,
  isDisabled,
  onClick,
  pricingNcStatus,
  slug,
}: OnboardingStepCardProps): JSX.Element => {
  const iconComponent = React.cloneElement(icon, {
    size: 'large',
    color: isDisabled ? 'interactive.icon.primary.disabled' : 'surface.icon.onCloud.onSubtle',
  });

  const shouldShowPricingNcBadge =
    pricingNcStatus === 'pending_agent_action' && slug === 'paymentMethods';

  return (
    <StyledCard
      isDisabled={isDisabled}
      data-testid={`onboarding-step-card-${title.replace(/\s+/g, '').toLowerCase()}`}
    >
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
              <Box
                display="flex"
                flexDirection={'column'}
                gap={'spacing.3'}
                alignItems={'flex-end'}
                position={{ base: 'absolute', l: 'static' }}
                top="0px"
                right="0px"
              >
                {!shouldShowPricingNcBadge && <StatusBadge type={status} size="medium" />}
                {shouldShowPricingNcBadge && (
                  <StatusBadge type={'pending_agent_action'} size="medium" />
                )}
              </Box>
            ) : null}
          </Box>
        </CardBody>
      </Card>
    </StyledCard>
  );
};

export default OnboardingStepCard;
