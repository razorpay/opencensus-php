import React from 'react';
import {
  Amount,
  Box,
  InfoIcon,
  Text,
  Tooltip,
  TooltipInteractiveWrapper,
  Heading,
} from '@razorpay/blade/components';

import { CardInfoShimmer } from 'merchant/views/Transactions/v2/Analytics/components/Shimmer';
import { StyledAmount } from 'merchant/views/Transactions/v2/Analytics/styled';
import { CardInfoProps } from 'merchant/views/Transactions/v2/Analytics/types';
import { TooltipWrapper } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/styled';
import { i18nifyConvertToMajorUnit } from 'merchant/views/Transactions/v2/common/utils';

const CardInfo = ({
  title,
  value,
  isAmount,
  subtitle,
  toolTipText,
  isLoading,
  currency,
  isLeader,
  isMobile,
}: CardInfoProps): JSX.Element => {
  return (
    <>
      <Box display="flex" alignItems="center" gap="spacing.2">
        <Text weight="semibold" marginX={isAmount ? 'spacing.2' : 'none'}>
          {title}
        </Text>
        <TooltipWrapper>
          <Tooltip content={toolTipText} placement="top">
            <TooltipInteractiveWrapper>
              <InfoIcon color="feedback.icon.neutral.intense" size="small" />
            </TooltipInteractiveWrapper>
          </Tooltip>
        </TooltipWrapper>
      </Box>
      {isLoading ? (
        <CardInfoShimmer />
      ) : (
        <>
          <Box marginTop="spacing.4">
            {isAmount ? (
              isMobile && isLeader ? (
                <StyledAmount>
                  <Amount
                    value={i18nifyConvertToMajorUnit(value, currency)}
                    isAffixSubtle={false}
                    suffix="decimals"
                    currency={currency}
                    type="heading"
                    size="xlarge"
                  />
                </StyledAmount>
              ) : (
                <Amount
                  value={i18nifyConvertToMajorUnit(value, currency)}
                  isAffixSubtle={true}
                  suffix="decimals"
                  currency={currency}
                  type="heading"
                  size="xlarge"
                />
              )
            ) : (
              <Heading color="surface.text.gray.normal" size={isLeader ? 'large' : 'small'}>
                {value}
              </Heading>
            )}
          </Box>
          <Text
            color="surface.text.gray.subtle"
            size="medium"
            marginX={isAmount ? 'spacing.2' : 'none'}
          >
            {subtitle}
          </Text>
        </>
      )}
    </>
  );
};

export default CardInfo;
