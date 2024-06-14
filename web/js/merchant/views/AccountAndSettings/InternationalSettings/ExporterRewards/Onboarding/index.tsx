import React, { useState } from 'react';
import { Box, Button, Text, Link, Heading } from '@razorpay/blade/components';

import ConsentPopup from './ConsentPopup';
import {
  OnboardingWrapper,
  OnboardingCardContainer,
  OnboardingCard,
  StyledImage,
} from '../components/styled';
import { EXPORTER_REWARDS_LINKS, ONBOARDING_CARDS } from '../constants';

const onKnowMoreClick = () => {
  // track
};

const ExporterRewardsOnBoarding = () => {
  const [isVisible, setVisible] = useState(false);

  const handleActionOpen = () => {
    setVisible(true);
  };

  const handleActionClose = () => {
    setVisible(false);
  };

  return (
    <OnboardingWrapper>
      <Box display="flex" flexDirection={{ base: 'column', m: 'row' }} marginBottom="spacing.3">
        <Text size="large" weight="semibold">
          What makes Cross Border Exporter Rewards great?
        </Text>
        <Link
          href={EXPORTER_REWARDS_LINKS.KNOW_MORE}
          target="_blank"
          rel="noopener noreferer"
          marginLeft="auto"
          onClick={onKnowMoreClick}
        >
          Know more
        </Link>
      </Box>
      <OnboardingCardContainer>
        {ONBOARDING_CARDS.map((feature, index) => (
          <OnboardingCard key={index}>
            <Box width="100px" marginBottom="spacing.3">
              <StyledImage src={feature.icon} alt={feature.title} />
            </Box>
            <Heading>{feature.title}</Heading>
            <Text size="medium" color="feedback.text.neutral.intense">
              {feature.desc}
            </Text>
          </OnboardingCard>
        ))}
      </OnboardingCardContainer>
      <Box display="flex">
        <Button marginLeft="auto" onClick={handleActionOpen}>
          Get started
        </Button>
      </Box>
      <ConsentPopup isVisible={isVisible} onClose={handleActionClose} />
    </OnboardingWrapper>
  );
};

export default ExporterRewardsOnBoarding;
