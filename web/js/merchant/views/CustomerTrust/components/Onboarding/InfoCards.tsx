import React from 'react';
import {
  Box,
  Text,
  AcceptPaymentsIcon,
  TagIcon,
  AnnouncementIcon,
  Link,
  type IconProps,
} from '@razorpay/blade/components';
import styled from 'styled-components';
import { BUYER_PROTECT_BLOG } from 'merchant/views/CustomerTrust/constants';
import { OnboardingCategory } from 'merchant/views/CustomerTrust/types';
import { trackBpKnowMoreClick } from 'merchant/views/CustomerTrust/analytics';

const InfoCardContainer = styled.div`
  background-color: #fff;
  border-radius: 8px;
`;

const CardsContainer = styled.div`
  background-color: #f8fafc;
  border-radius: 8px;
  overflow: hidden;
`;

const InfoCard = ({
  icon: Icon,
  text,
  highlightedText,
}: {
  icon: React.ComponentType<IconProps>;
  text: string;
  highlightedText: string;
}) => {
  if (!highlightedText || !text.includes(highlightedText)) {
    return <Text>{text}</Text>;
  }

  const [before, after] = text.split(highlightedText);

  return (
    <InfoCardContainer>
      <Box padding="20px" display="flex" flexDirection="column" gap="spacing.3">
        <Icon size="large" color="surface.icon.onCloud.onSubtle" />

        <Text size="small" color="surface.text.gray.muted">
          {before}
          <Text as="span" size="small" weight="semibold" color="surface.text.gray.subtle">
            {highlightedText}
          </Text>
          {after}
        </Text>
      </Box>
    </InfoCardContainer>
  );
};

export const InfoCards = ({
  pricing,
  category,
}: {
  pricing?: number;
  category: OnboardingCategory;
}) => {
  // Format pricing to show whole numbers without decimal places
  const formatPricing = (value?: number): string => {
    if (value === undefined) return '1';
    return Number.isInteger(value) ? value.toString() : value.toFixed(2);
  };

  const handleKnowMoreClick = () => {
    trackBpKnowMoreClick();
    window.open(BUYER_PROTECT_BLOG, '_blank');
  };

  return (
    <CardsContainer>
      <Box
        display="flex"
        flexDirection="column"
        gap="spacing.7"
        padding={{ base: 'spacing.5', s: 'spacing.7' }}
        backgroundColor="surface.background.gray.subtle"
      >
        <Box display="flex" flexDirection="column" gap="spacing.7">
          <Box
            display="flex"
            flexDirection={{ base: 'column', s: 'row' }}
            alignItems={{ base: 'flex-start', s: 'center' }}
            justifyContent="space-between"
            gap={{ base: 'spacing.4', s: 'spacing.0' }}
          >
            <Text weight="semibold" color="surface.text.gray.subtle">
              What You Need to Know About Buyer Protection
            </Text>
            <Link variant="anchor" color="primary" size="medium" onClick={handleKnowMoreClick}>
              Know more
            </Link>
          </Box>

          <Box
            display="flex"
            flexDirection={{ base: 'column', s: 'row' }}
            alignItems={{ base: 'stretch', s: 'center' }}
            gap={{ base: 'spacing.5', s: '21px' }}
          >
            <InfoCard
              icon={AcceptPaymentsIcon}
              text="Razorpay handles refunds effortlessly at no cost to you."
              highlightedText="Razorpay handles refunds effortlessly"
            />

            {category === 'rtb' ? (
              <InfoCard
                icon={TagIcon}
                text={`Enjoy 1-month free trial & continue at ${formatPricing(
                  pricing,
                )}% + GST on prepaid transactions`}
                highlightedText="1-month free trial"
              />
            ) : (
              <InfoCard
                icon={TagIcon}
                text={`Protect your buyers at just ${formatPricing(
                  pricing,
                )}% + GST on only your prepaid transactions`}
                highlightedText="1% + GST on only your prepaid transactions"
              />
            )}

            <InfoCard
              icon={AnnouncementIcon}
              text="Enjoy the flexibility to opt out — no long-term commitments."
              highlightedText="flexibility to opt out"
            />
          </Box>
        </Box>
      </Box>
    </CardsContainer>
  );
};
