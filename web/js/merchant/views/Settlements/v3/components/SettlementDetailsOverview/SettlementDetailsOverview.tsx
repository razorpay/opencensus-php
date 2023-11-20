import React from 'react';
import { Badge, Box, Card, CardBody, Text } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import Amount from 'common/ui/Amount';
import { titleCase } from 'common/utils/rzp-utils';
import { INR_CURRENCY } from 'merchant/views/Capital/CashAdvanceNudges/constants';
import {
  OverviewSubtextWrapper,
  StyledAmountWrapper,
} from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/styled';
import { BADGE_INFO } from 'merchant/views/Settlements/components/utils';
import {
  getBaseVariant,
  getTime as useTime,
} from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/utils';
import { SettlementPropsInterface } from 'merchant/views/Settlements/v3/typings';
import Tooltip from 'merchant/views/Settlements/v3/components/Tooltip';
import { User } from 'common/typings';
import { OverviewIcon } from 'merchant/views/Transactions/v2/Payments/components/PaymentsDetails/PaymentDetailsOverview';
import { useMobile } from 'common/hooks/useMobile';

interface ISettlementDetailsOverview {
  settlement: SettlementPropsInterface;
  user: Required<User>;
}

function SettlementDetailsOverview({ settlement, user }: ISettlementDetailsOverview) {
  const { amount, created_at, status } = settlement;
  const {
    merchant: { currency },
  } = user;
  const [createdDay, createdTime] = useTime(created_at);
  const isMobile = useMobile();

  return (
    <Box testID="settlement-details-overview">
      <Card elevation="none">
        <CardBody>
          <Box display="flex" gap="spacing.5">
            {!isMobile && (
              <Box height="60px" width="60px">
                <OverviewIcon status={status} />
              </Box>
            )}
            {isMobile ? (
              <Box flex="1" display="flex" flexDirection="column">
                <Box display="flex" flexDirection="column" alignItems="center">
                  <Box display="flex" alignItems="center">
                    <Badge
                      contrast="low"
                      marginRight="spacing.3"
                      marginTop="spacing.2"
                      size="large"
                      variant={getBaseVariant(status)}
                      icon={(props) => (
                        <Tooltip content={BADGE_INFO[status.toUpperCase()]} {...props} />
                      )}
                    >
                      {titleCase(status)}
                    </Badge>
                  </Box>
                  <StyledAmountWrapper type="regular" fontSize={28}>
                    <Amount currency={currency || INR_CURRENCY} value={amount} />
                  </StyledAmountWrapper>
                </Box>
                <Box display="flex" justifyContent="center" marginTop="spacing.3">
                  <OverviewSubtextWrapper isMobile={isMobile}>
                    <Text color="surface.text.normal.lowContrast" css={{ display: 'flex' }}>
                      Created on {createdDay},
                      <Text color="surface.text.subdued.lowContrast">{createdTime}</Text>
                    </Text>
                  </OverviewSubtextWrapper>
                </Box>
              </Box>
            ) : (
              <Box
                flex="1"
                display="flex"
                flexDirection="column"
                justifyContent="center"
                gap="spacing.1"
              >
                <Box display="flex" gap="spacing.3">
                  <StyledAmountWrapper type="regular" fontSize={28}>
                    <Amount currency={currency || INR_CURRENCY} value={amount} />
                  </StyledAmountWrapper>

                  <Box display="flex" alignItems="center">
                    <Badge
                      contrast="low"
                      marginRight="spacing.3"
                      marginTop="spacing.2"
                      size="large"
                      variant={getBaseVariant(status)}
                      icon={(props) => (
                        <Tooltip content={BADGE_INFO[status.toUpperCase()]} {...props} />
                      )}
                    >
                      {titleCase(status)}
                    </Badge>
                  </Box>
                </Box>
                <OverviewSubtextWrapper isMobile={isMobile}>
                  <Text color="surface.text.normal.lowContrast" css={{ display: 'flex' }}>
                    Created on {createdDay},
                    <Text color="surface.text.subdued.lowContrast">{createdTime}</Text>
                  </Text>
                </OverviewSubtextWrapper>
              </Box>
            )}
          </Box>
        </CardBody>
      </Card>
    </Box>
  );
}

const mapStateToProps = (state) => {
  const { settlement, session } = state;
  return {
    settlement: settlement.settlement,
    user: session.user,
  };
};

export default connect(mapStateToProps, null)(SettlementDetailsOverview);
