import {
  Amount,
  Box,
  InfoIcon,
  Text,
  Tooltip,
  TooltipInteractiveWrapper,
  Title,
} from '@razorpay/blade/components';
import React from 'react';
import { CardInfoShimmer } from 'merchant/views/Transactions/v2/Analytics/components/Shimmer';
import { CardInfoProps } from 'merchant/views/Transactions/v2/Analytics/types';
import { TooltipWrapper } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/styled';
import { paiseToRupees } from 'common/utils/rzp-utils';

const CardInfo = ({
  title,
  value,
  isAmount,
  subtitle,
  toolTipText,
  isLoading,
  currency,
  isLeader,
}: CardInfoProps): JSX.Element => {
  return (
    <>
      <Box display="flex" alignItems="center" gap="spacing.2">
        <Text weight="bold" marginX={isAmount ? 'spacing.2' : 'none'}>
          {title}
        </Text>
        <TooltipWrapper>
          <Tooltip content={toolTipText} placement="top">
            <TooltipInteractiveWrapper>
              <InfoIcon color="feedback.icon.neutral.lowContrast" size="small" />
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
              <Amount
                value={paiseToRupees(value)}
                isAffixSubtle={true}
                suffix="decimals"
                currency={currency}
                size={isLeader ? 'title-medium' : 'title-small'}
              />
            ) : (
              <Title color="surface.text.normal.lowContrast" size="small">
                {value}
              </Title>
            )}
          </Box>
          <Text
            color="surface.text.subtle.lowContrast"
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
