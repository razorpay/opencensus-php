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
import SettlementCycle  from '@dashboards/payments/views/Settlements/components/SettlementScheduleV2';
import { paiseToRupees } from '@libs/shared-utils';
import { useStore } from '@federated/apps/shell/commonStore';
import { getLandingPageAnalyticsToolTip } from 'apps/self-serve/src/App/Transactions/v2/Analytics/utils';
import { CapturedPaymentCardProps } from 'apps/self-serve/src/App/Transactions/v2/Analytics/types';
import {
  BoxWithWordBreak,
  StyledAmount,
} from 'apps/self-serve/src/App/Transactions/v2/Analytics/styled';
import { TooltipWrapper } from 'apps/self-serve/src/App/Transactions/v2/Payments/components/PaymentsDetails/styled';
import { track } from 'apps/self-serve/src/App/Transactions/v2/common/tracking';
import { useI18Service } from '@libs/web-nexus/common/i18';

const CapturedPaymentCard = ({
  currency,
  paymentCapturedAmount,
  paymentCapturedCount,
  isMobile,
  durationOption,
}: CapturedPaymentCardProps): JSX.Element => {
  const openModal = useStore((state) => state.openModal);
  const { isConfigTagEnabled } = useI18Service();

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
  const orgName = window.rzp_org?.business_name;

  return (
    <Card
      padding="spacing.3"
      marginY="spacing.5"
      backgroundColor="surface.background.gray.moderate"
      elevation="none"
      display="flex"
    >
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
              <Text weight="semibold" size="medium" color="surface.text.gray.subtle">
                Collected Amount
              </Text>
              <TooltipWrapper>
                <Tooltip
                  content={getLandingPageAnalyticsToolTip(orgName).Collected}
                  placement="top"
                >
                  <TooltipInteractiveWrapper>
                    <InfoIcon color="feedback.icon.neutral.intense" size="small" />
                  </TooltipInteractiveWrapper>
                </Tooltip>
              </TooltipWrapper>
            </Box>
            <StyledAmount>
              <Amount
                suffix="decimals"
                currency={currency}
                value={paiseToRupees(paymentCapturedAmount)}
                isAffixSubtle={false}
                type="heading"
                size="xlarge"
              />
            </StyledAmount>
            <Text as="div" marginX="spacing.2" color="surface.text.gray.subtle">
              from {paymentCapturedCount} captured payments
            </Text>
          </Box>
          {paymentCapturedAmount > 0 && !isConfigTagEnabled('settlements.settlement') ? (
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
