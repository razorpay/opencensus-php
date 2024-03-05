import {
  Amount,
  Box,
  Card,
  CardBody,
  InfoIcon,
  Link,
  Text,
  Tooltip,
  TooltipInteractiveWrapper,
} from '@razorpay/blade/components';
import React from 'react';
import SettlementCycle from 'shell/SettlementCycle';
import { paiseToRupees } from '@dashboard/shared-utils/rzp-utils';
import { useStore } from 'shell/commonStore';
import { LandingPageAnalyticsToolTip } from 'apps/self-serve/src/App/Transactions/v2/Analytics/utils';
import { CapturedPaymentCardProps } from 'apps/self-serve/src/App/Transactions/v2/Analytics/types';
import {
  BoxWithWordBreak,
  StyledAmount,
} from 'apps/self-serve/src/App/Transactions/v2/Analytics/styled';
import { TooltipWrapper } from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsDetails/styled';
import { track } from 'apps/self-serve/src/App/Transactions/v2/common/tracking';

const CapturedPaymentCard = ({
  currency,
  paymentCapturedAmount,
  paymentCapturedCount,
  isMobile,
  durationOption,
}: CapturedPaymentCardProps): JSX.Element => {
  const openModal = useStore((state) => state.openModal);
  const viewSettlementCycle = () => {
    openModal({
      size: 'medium',
      component: <SettlementCycle />,
    });
    track({
      objectName: 'Settlement Cycle',
      properties: {
        overviewDate: durationOption?.title,
        initiatePoint: 'Payments Collected Card',
        section: 'Overview',
      },
    });
  };
  return (
    <Card padding="spacing.3" marginY="spacing.5" surfaceLevel={2} elevation="none" display="flex">
      <CardBody>
        <Box
          display="flex"
          padding="spacing.4"
          paddingLeft={isMobile ? 'spacing.2' : 'spacing.5'}
          gap="spacing.2"
          flexDirection="column"
          minWidth="280px"
          height={paymentCapturedAmount > 0 ? '200px' : undefined}
          justifyContent="space-between"
        >
          <Box gap="spacing.3" display="flex" flexDirection="column">
            <Box display="flex" gap="spacing.2" alignItems="center" marginX="spacing.2">
              <Text type="subtle" weight="bold" contrast="low" size="medium">
                Collected Amount
              </Text>
              <TooltipWrapper>
                <Tooltip content={LandingPageAnalyticsToolTip.Collected} placement="top">
                  <TooltipInteractiveWrapper>
                    <InfoIcon color="feedback.icon.neutral.lowContrast" size="small" />
                  </TooltipInteractiveWrapper>
                </Tooltip>
              </TooltipWrapper>
            </Box>
            <StyledAmount>
              <Amount
                suffix="decimals"
                currency={currency}
                size="title-medium"
                value={paiseToRupees(paymentCapturedAmount)}
                isAffixSubtle={false}
              />
            </StyledAmount>
            <Text type="subtle" as="div" marginX="spacing.2">
              from {paymentCapturedCount} captured payments
            </Text>
          </Box>
          {paymentCapturedAmount > 0 ? (
            <BoxWithWordBreak>
              <Text size={isMobile ? 'small' : 'medium'} as="div" marginX="spacing.2">
                Net settlement amount will be deposited as per your&nbsp;
                <Link
                  size={isMobile ? 'small' : 'medium'}
                  variant="button"
                  onClick={viewSettlementCycle}
                >
                  settlement cycle
                </Link>
              </Text>
            </BoxWithWordBreak>
          ) : null}
        </Box>
      </CardBody>
    </Card>
  );
};

export default CapturedPaymentCard;
