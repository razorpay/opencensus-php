import React from 'react';
import { EmptyCardProp } from './types';
import { InsightCardHeader } from './InsightCard';
import {
  Box,
  Heading,
  Text,
  Badge,
  Divider,
  RayIcon,
  Amount,
  ArrowSquareUpIcon,
} from '@razorpay/blade/components';
import { staticContent } from './constants';
import { paiseToRupees } from '@libs/shared-utils';
import { getAmountSuffix } from './utils';

const EmptyCard = ({
  insightsStaticData,
  isMobile,
  componentData,
  analytics,
  cardType,
  dataSummary,
}: EmptyCardProp) => {
  const trendColour = 'feedback.text.positive.intense';
  const hasInputTime = dataSummary && dataSummary.input_time;
  const amountSuffixText = hasInputTime ? getAmountSuffix(dataSummary.input_time) : '';

  return (
    <Box
      margin={{
        base: 'spacing.0',
        s: 'spacing.0',
        m: 'spacing.5',
        l: 'spacing.5',
      }}
      display="flex"
      flexDirection="column"
      flexGrow="1"
    >
      <InsightCardHeader
        insightsStaticData={insightsStaticData}
        isMobile={isMobile}
        componentData={componentData}
        analytics={analytics}
        showViewDetails={false}
      />
      <Box display="flex" flexDirection="column" justifyContent="space-between">
        <Box
          display="flex"
          alignItems="baseline"
          gap={{ base: 'spacing.3', s: 'spacing.3', m: 'spacing.7', l: 'spacing.7' }}
        >
          {cardType === 'success_rate' ? (
            <Heading size="2xlarge" color="surface.text.gray.normal" weight="semibold">
              0%
            </Heading>
          ) : (
            <Amount
              value={0}
              isAffixSubtle
              suffix="humanize"
              currencyIndicator="currency-symbol"
              currency="INR"
              size="2xlarge"
              color="surface.text.gray.normal"
              weight="semibold"
              type="heading"
            />
          )}

          <Box display="flex" gap="spacing.2" alignItems="center" transform={`translateY(3px)`}>
            <ArrowSquareUpIcon
              color="feedback.icon.positive.intense"
              size={isMobile ? 'medium' : 'xlarge'}
            />
            {isMobile ? (
              <Text size="xsmall" color={trendColour} weight="semibold">
                0% {amountSuffixText}
              </Text>
            ) : (
              <Text
                variant="body"
                size="medium"
                color={trendColour}
                weight="semibold"
                display="flex"
              >
                0%&nbsp;
                <Text
                  variant="body"
                  display={{ base: 'none', m: 'none', l: 'block' }}
                  size="medium"
                  color={trendColour}
                  weight="semibold"
                >
                  {amountSuffixText}
                </Text>
              </Text>
            )}
          </Box>
        </Box>
        <Box>
          <Box
            display="flex"
            flexDirection="row"
            alignItems="center"
            gap="spacing.3"
            marginBottom="spacing.5"
            marginTop="spacing.7"
          >
            <Badge color="neutral" icon={RayIcon} size="medium" emphasis="subtle">
              {staticContent.rayInsightBadgeText}
            </Badge>
            <Divider />
          </Box>
          <Box>
            <Text size="medium" weight="medium" color="surface.text.gray.muted">
              {insightsStaticData.emptyCardDescription}
            </Text>
          </Box>
        </Box>
      </Box>
    </Box>
  );
};
export default EmptyCard;
