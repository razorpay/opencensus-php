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
import { paiseToRupees } from '@dashboard/shared-utils/rzp-utils';
import { CardInfoShimmer } from 'apps/self-serve/src/App/Transactions/v2/Analytics/components/Shimmer';
import { CardInfoProps } from 'apps/self-serve/src/App/Transactions/v2/Analytics/types';
import { TooltipWrapper } from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsDetails/styled';
import { StyledAmount } from 'apps/self-serve/src/App/Transactions/v2/Analytics/styled';

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
              isMobile && isLeader ? (
                <StyledAmount>
                  <Amount
                    value={paiseToRupees(value)}
                    isAffixSubtle={false}
                    suffix="decimals"
                    currency={currency}
                    size="title-medium"
                  />
                </StyledAmount>
              ) : (
                <Amount
                  value={paiseToRupees(value)}
                  isAffixSubtle={true}
                  suffix="decimals"
                  currency={currency}
                  size="title-medium"
                />
              )
            ) : (
              <Title color="surface.text.normal.lowContrast" size={isLeader ? 'large' : 'small'}>
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
