import React, { ReactElement } from 'react';
import {
  Amount,
  Box,
  Heading,
  InfoIcon,
  Tooltip,
  Text,
  TooltipInteractiveWrapper,
  Spinner,
} from '@razorpay/blade/components';
import { formatNumber } from '@razorpay/i18nify-js/currency';

import SummaryTileBg from '@apps/digital-bills/src/assets/summary-tile-bg.svg';
import RetryOnError from '@apps/digital-bills/src/common/components/RetryOnError';

type SummaryCardProps = {
  title: string;
  amount: number;
  subtitle?: string;
  info?: string;
  heroImg: string;
  showCurrency?: boolean;
  isLoading?: boolean;
  hasError?: boolean;
  retryFn?: () => void;
};

type HeroImgContainerProps = {
  heroImg: string;
};

const HeroImgContainer = ({ heroImg }: HeroImgContainerProps): ReactElement => {
  return (
    <Box position="relative" display="flex" alignItems="flex-end" justifyContent="center">
      <Box position="absolute" marginBottom="spacing.3">
        <img src={heroImg} alt="" />
      </Box>
      <img alt="Summary card" src={SummaryTileBg} />
    </Box>
  );
};

const SummaryCard = (props: SummaryCardProps): React.ReactElement => {
  const {
    title,
    info,
    amount = 0,
    showCurrency = false,
    subtitle,
    heroImg,
    isLoading = false,
    hasError = false,
    retryFn,
  } = props;
  const formatted = formatNumber(amount, {
    intlOptions: {
      notation: 'compact',
      maximumFractionDigits: 2,
      trailingZeroDisplay: 'stripIfInteger',
      currencyDisplay: undefined,
    },
  });

  const renderContent = () => {
    if (hasError) return <RetryOnError retryFn={retryFn} justifyContent="flex-start" />;
    return isLoading ? (
      <Spinner
        alignSelf="flex-start"
        color="primary"
        label=""
        accessibilityLabel={`${title} spinner`}
      />
    ) : showCurrency ? (
      <Amount type="heading" size="xlarge" weight="semibold" suffix="humanize" value={amount} />
    ) : (
      <Heading weight="semibold" size="xlarge">
        {formatted}
      </Heading>
    );
  };

  return (
    <Box
      display="flex"
      backgroundColor="surface.background.gray.intense"
      borderRadius="small"
      flex="1"
      width={{ base: '100%', m: 'auto' }}
    >
      {heroImg ? (
        <Box marginRight={{ base: 'spacing.3', m: 'spacing.6' }}>
          <HeroImgContainer heroImg={heroImg} />
        </Box>
      ) : null}
      <Box
        justifyContent="space-between"
        flex="1"
        display="flex"
        flexDirection="column"
        padding="spacing.4"
        gap="spacing.5"
      >
        <Box display="flex" alignItems={'center'} gap="spacing.4">
          <Box display="flex" alignItems="center" gap="spacing.3" flexWrap="wrap">
            <Heading weight="regular" size="medium">
              {title}
            </Heading>
            {subtitle ? (
              <Text
                marginTop={{ base: 'none', m: 'auto' }}
                size="small"
                variant="caption"
                color="surface.text.gray.muted"
              >
                {subtitle}
              </Text>
            ) : null}
          </Box>
          {info ? (
            <Tooltip content={info}>
              <TooltipInteractiveWrapper>
                <InfoIcon size="large" color="surface.icon.gray.muted" />
              </TooltipInteractiveWrapper>
            </Tooltip>
          ) : null}
        </Box>
        {renderContent()}
      </Box>
    </Box>
  );
};

export default SummaryCard;
