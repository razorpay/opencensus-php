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
import { LandingPageAnalyticsToolTip } from 'merchant/views/Transactions/v2/Analytics/utils';
import { CapturedPaymentCardProps } from 'merchant/views/Transactions/v2/Analytics/types';
import { BoxWithWordBreak, StyledAmount } from 'merchant/views/Transactions/v2/Analytics/styled';
import { openModal as fnOpenModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import SettlementCycle from 'merchant/views/Settlements/components/SettlementScheduleV2';
import { TooltipWrapper } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/styled';
import { track } from 'merchant/views/Transactions/v2/common/tracking';
import { paiseToRupees } from 'common/utils/rzp-utils';

const CapturedPaymentCard = ({
  openModal,
  currency,
  paymentCapturedAmount,
  paymentCapturedCount,
  isMobile,
  durationOption,
}: CapturedPaymentCardProps): JSX.Element => {
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
                <Tooltip content={LandingPageAnalyticsToolTip.Collected} placement="top">
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

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      openModal: fnOpenModal,
    },
    dispatch,
  );
};

export default connect(null, mapDispatchToProps)(CapturedPaymentCard);
