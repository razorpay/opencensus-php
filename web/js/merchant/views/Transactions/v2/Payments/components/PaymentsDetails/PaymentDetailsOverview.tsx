import React, { useEffect } from 'react';
import { Badge, Box, Card, CardBody, Divider, Text, useTheme } from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import { bindActionCreators, compose } from 'redux';

import Amount from 'common/ui/Amount';
import { titleCase } from 'common/utils/rzp-utils';
import {
  fetchSchedule,
  fetchHolidayList,
  fetchSettlementConfig,
} from 'merchant/reducers/settlements/details';
import * as ModalActions from 'merchant_common/reducers/modals';

import Tooltip from './Tooltip';
import { OverviewIconWrapper, OverviewSubtextWrapper, StyledAmountWrapper } from './styled';
import { IPaymentDetails, IPaymentIdRefundDetails, ApplicationDetails } from './types';

import {
  getBadgeIcon,
  getBaseVariant,
  getTime as useTime,
  getRefundsOverviewDetails,
  getDisputesOverviewDetails,
} from './utils';
import { ERROR_DESCRIPTION_CONTENT_MAP } from './constants';

export const OverviewIcon = ({ status }) => {
  const Icon = getBadgeIcon(status);
  return <OverviewIconWrapper>{Icon}</OverviewIconWrapper>;
};

interface IPaymentDetailsOverview extends RouteComponentProps {
  paymentDetails: IPaymentDetails;
  paymentIdRefundDetails: IPaymentIdRefundDetails;
  applicationDetails: ApplicationDetails;
  fetchHolidayList: () => Promise<Record<string, string>>;
  fetchSchedule: () => Promise<Record<string, string>>;
  fetchSettlementConfig: () => Promise<Record<string, string>>;
  openModal: (args) => void;
}

function PaymentDetailsOverview({
  paymentDetails,
  paymentIdRefundDetails,
  applicationDetails,
  fetchHolidayList,
  fetchSchedule,
  fetchSettlementConfig,
  history,
}: IPaymentDetailsOverview) {
  const { currency, amount, created_at, status } = paymentDetails;
  const settlementId = paymentDetails?.transaction?.settlement_id;
  const [createdDay, createdTime] = useTime(created_at);

  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({
    breakpoints: theme.breakpoints,
  });
  const isMobile = matchedDeviceType === 'mobile';

  useEffect(() => {
    if (!settlementId) {
      fetchSettlementConfig();
      fetchSchedule();
      fetchHolidayList();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [settlementId]);

  const viewDisputeCallback = (route: string) => history.push(route);

  return (
    <Box testID="payment-details-overview">
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
                      emphasis="subtle"
                      marginRight="spacing.3"
                      marginTop="spacing.2"
                      size="large"
                      color={getBaseVariant(status)}
                      icon={(props) => <Tooltip type={status} {...props} />}
                    >
                      {titleCase(status)}
                    </Badge>
                    {applicationDetails?.name ? (
                      <Badge
                        emphasis="subtle"
                        marginRight="spacing.3"
                        marginTop="spacing.2"
                        size="large"
                        color={getBaseVariant(status)}
                      >
                        Payment initiated via {applicationDetails?.name}
                      </Badge>
                    ) : null}
                  </Box>
                  <StyledAmountWrapper type="regular" fontSize={28}>
                    <Amount currency={currency || 'INR'} value={amount} />
                  </StyledAmountWrapper>
                </Box>
                <Box display="flex" justifyContent="center" marginTop="spacing.3">
                  <OverviewSubtextWrapper isMobile={isMobile}>
                    <Text color="surface.text.gray.normal" css={{ display: 'flex' }}>
                      Created on {createdDay},
                      <Text color="surface.text.gray.muted">{createdTime}</Text>
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
                    <Amount currency={currency || 'INR'} value={amount} />
                  </StyledAmountWrapper>

                  <Box display="flex" alignItems="center">
                    <Badge
                      emphasis="subtle"
                      marginRight="spacing.3"
                      marginTop="spacing.2"
                      size="large"
                      color={getBaseVariant(status)}
                      icon={(props) => <Tooltip type={status} {...props} />}
                    >
                      {titleCase(status)}
                    </Badge>
                    {applicationDetails?.name ? (
                      <Badge
                        emphasis="subtle"
                        marginRight="spacing.3"
                        marginTop="spacing.2"
                        size="large"
                        color={getBaseVariant(status)}
                      >
                        Payment initiated via {applicationDetails?.name}
                      </Badge>
                    ) : null}
                  </Box>
                </Box>
                <OverviewSubtextWrapper isMobile={isMobile}>
                  <Text color="surface.text.gray.normal" css={{ display: 'flex' }}>
                    Created on {createdDay},
                    <Text color="surface.text.gray.muted">{createdTime}</Text>
                  </Text>
                </OverviewSubtextWrapper>
              </Box>
            )}
          </Box>
          {paymentDetails?.status === 'failed' && paymentDetails?.error_description && (
            <Box marginTop="spacing.5">
              <Divider marginBottom="spacing.3" />
              <Text variant="body" size="small" weight="semibold" color="surface.text.gray.normal">
                {ERROR_DESCRIPTION_CONTENT_MAP[paymentDetails?.error_description]
                  ? ERROR_DESCRIPTION_CONTENT_MAP[paymentDetails?.error_description]
                  : paymentDetails?.error_description}
              </Text>
            </Box>
          )}
          {(paymentDetails?.status === 'refunded' || paymentIdRefundDetails.length > 0) &&
            paymentDetails.disputes.items.length === 0 && (
              <Box marginTop="spacing.5">
                <Divider marginBottom="spacing.3" />
                {getRefundsOverviewDetails(paymentIdRefundDetails)}
              </Box>
            )}
          {paymentDetails.disputes.items.length > 0 && (
            <Box marginTop="spacing.5">
              <Divider marginBottom="spacing.3" />
              {getDisputesOverviewDetails(paymentDetails, viewDisputeCallback)}
            </Box>
          )}
        </CardBody>
      </Card>
      {/* TODO: keep it back once tax and fee currency value is fixed for international payments
      <BoxContainer disableMarginTop>
        <CardWrapper enableBorderTopRadius>
          <Card padding="spacing.5" elevation="none">
            <CardBody>
              <RowsWrapper>
                <Box display="flex" justifyContent="space-between" paddingY="spacing.3">
                  <Text type="subtle" size="medium" weight="bold">
                    Gross amount
                  </Text>
                  <StyledAmountWrapper
                    data-testid="gross-amount"
                    type="positive"
                    fontSize={theme.typography.fonts.size[100]}
                  >
                    <Amount
                      size="body-medium-bold"
                      value={amount}
                      isAffixSubtle={false}
                      currency={currency}
                      intent="positive"
                    />
                  </StyledAmountWrapper>
                </Box>
                <DashedDivider />
                <Box position="relative" paddingY="spacing.3">
                  <CollapsibleContainer onClick={toggleDeductions}>
                    <Text type="subtle" size="medium" weight="bold">
                      Deductions{' '}
                      <StyledChevron>
                        {!isOpen ? (
                          <ChevronDownIcon
                            size="medium"
                            color="feedback.icon.neutral.intense"
                            data-testid="chevron-down"
                          />
                        ) : (
                          <ChevronUpIcon
                            size="medium"
                            color="feedback.icon.neutral.intense"
                            data-testid="chevron-up"
                          />
                        )}
                      </StyledChevron>
                    </Text>
                  </CollapsibleContainer>
                  {isOpen && (
                    <>
                      <Box
                        display="flex"
                        justifyContent="space-between"
                        paddingTop="spacing.3"
                        paddingLeft="spacing.3"
                      >
                        <Text>
                          Razorpay platform fees{' '}
                          <Tooltip
                            type={
                              applicationDetails?.name ? 'partnerApplicationFees' : 'platformFees'
                            }
                            partnerApplicationName={applicationDetails?.name}
                            size="small"
                          />
                        </Text>
                        <Amount value={fee} />
                      </Box>
                      <Box
                        display="flex"
                        justifyContent="space-between"
                        paddingTop="spacing.3"
                        paddingLeft="spacing.3"
                      >
                        <Text>
                          GST <Tooltip type="gst" size="small" />
                        </Text>
                        <Amount value={tax} />
                      </Box>
                    </>
                  )}
                  <StyledAmountContainer>
                    <StyledAmountWrapper
                      type="negative"
                      fontSize={theme.typography.fonts.size[100]}
                    >
                      <Amount value={totalDeductions} currency={currency} />
                    </StyledAmountWrapper>
                  </StyledAmountContainer>
                </Box>
                <Divider dividerStyle="solid" thickness="thick" variant="normal" />
                <Box display="flex" justifyContent="space-between" paddingY="spacing.3">
                  <Text type="normal" size="medium" weight="bold">
                    Net amount
                  </Text>
                  <StyledAmountWrapper
                    data-testid="net-amount"
                    type="regular"
                    fontSize={theme.typography.fonts.size[100]}
                  >
                    <Amount value={netAmount} currency={currency} />
                  </StyledAmountWrapper>
                </Box>
              </RowsWrapper>
            </CardBody>
          </Card>
        </CardWrapper>
        <SectionFooter>
          {paymentDetails?.transaction?.settlement_id ? (
            <Text type="subtle" variant="body" size="small" weight="regular" contrast="low">
              Net amount deposited in your bank account{' '}
              <Link
                size="small"
                icon={ChevronRightIcon}
                iconPosition="right"
                onClick={() => {
                  history.push(`/settlements/${paymentDetails?.transaction?.settlement_id}`, {
                    prevPath: pathname,
                  });
                }}
              >
                View details
              </Link>
            </Text>
          ) : (
            <Text type="subtle" variant="body" size="small" weight="regular" contrast="low">
              Net amount is deposited in your bank account as per your{' '}
              <Link
                size="small"
                icon={ChevronRightIcon}
                iconPosition="right"
                onClick={viewSettlementSchedule}
              >
                settlement cycle
              </Link>
            </Text>
          )}
        </SectionFooter>
      </BoxContainer> */}
    </Box>
  );
}

const mapStateToProps = (state) => {
  return {
    user: state.session.user,
    settlementConfig: state.settlement.config,
    schedule: state.settlement.schedule,
    holidayList: state.settlement.holidayList,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      ...ModalActions,
      fetchHolidayList,
      fetchSchedule,
      fetchSettlementConfig,
    },
    dispatch,
  );
};

export default compose<any>(
  withRouter,
  connect(mapStateToProps, mapDispatchToProps),
)(PaymentDetailsOverview);
