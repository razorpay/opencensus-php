// TODO: Fix the imports, currently out of scope
// @ts-nocheck

import React, { useEffect, useState } from 'react';
import {
  Badge,
  Box,
  Card,
  CardBody,
  ChevronDownIcon,
  ChevronRightIcon,
  ChevronUpIcon,
  Divider,
  Link,
  Text,
  useTheme,
  Amount as BladeAmount,
} from '@razorpay/blade/components';
import { useBreakpoint } from '@razorpay/blade/utils';
import { connect } from 'react-redux';
import { withRouter } from '@libs/web-nexus/common/deprecated/withRouter';
import { AnyAction, Dispatch, bindActionCreators, compose } from 'redux';

import Amount from '@libs/web-nexus/common//ui/Amount';
import { toTitleCase } from '@libs/shared-utils';
import {
  fetchSchedule,
  fetchHolidayList,
  fetchSettlementConfig,
} from '@dashboards/payments/reducers/settlements/details';

import type { RouteComponentProps } from 'apps/self-serve/src/App/Transactions/v2/Payments/types';
import Tooltip from './Tooltip';
import {
  BoxContainer,
  CardWrapper,
  CollapsibleContainer,
  DashedDivider,
  OverviewIconWrapper,
  OverviewSubtextWrapper,
  RowsWrapper,
  SectionFooter,
  StyledAmountContainer,
  StyledAmountWrapper,
  StyledChevron,
} from './styled';
import { IPaymentDetails, IPaymentIdRefundDetails, ApplicationDetails } from './types';

import {
  getBadgeIcon,
  getBaseVariant,
  getTime as useTime,
  getRefundsOverviewDetails,
  getDisputesOverviewDetails,
} from './utils';
import { ERROR_DESCRIPTION_CONTENT_MAP } from './constants';
import { useLocation } from 'react-router-dom';
import { trackDetailsClick } from 'apps/self-serve/src/App/Transactions/v2/common/tracking';
import SettlementScheduleV2 from '@dashboards/payments/views/Settlements/components/SettlementScheduleV2';
import { i18nifyConvertToMajorUnit } from 'apps/self-serve/src/App/Transactions/v2/common/utils';
import { useStore } from '@apps/shell/src/client/store/commonStore';

const OverviewIcon = ({ status }: { status: IPaymentDetails['status'] }) => {
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
  const { openModal } = useStore((state) => ({
    openModal: state.openModal,
  }));
  const [isDeductionBreakdownOpen, setIsDeductionBreakdownOpen] = useState(false);
  const toggleDeductions = () => {
    setIsDeductionBreakdownOpen((prevValue) => !prevValue);
  };
  const { currency, fee, tax, amount, created_at, status } = paymentDetails;
  const totalDeductions = fee + tax;
  const netAmount = amount - totalDeductions;
  const settlementId = paymentDetails?.transaction?.settlement_id;
  const [createdDay, createdTime] = useTime(created_at);

  const { pathname } = useLocation();
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

  const viewSettlementSchedule = () => {
    trackDetailsClick({ objectName: 'View Settlement Cycle' });
    openModal({
      size: 'medium',
      component: <SettlementScheduleV2 />,
    });
  };

  return (
    <Box testID="payment-details-overview">
      <Card elevation="none">
        <CardBody>
          <Box display="flex" gap="spacing.5">
            {!isMobile ? (
              <Box height="60px" width="60px">
                <OverviewIcon status={status} />
              </Box>
            ) : null}
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
                      {toTitleCase(status)}
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
                    <Text color="surface.text.gray.normal">
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
                      {toTitleCase(status)}
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
                  <Text color="surface.text.gray.normal">
                    Created on {createdDay},
                    <Text color="surface.text.gray.muted">{createdTime}</Text>
                  </Text>
                </OverviewSubtextWrapper>
              </Box>
            )}
          </Box>
          {paymentDetails?.status === 'failed' && paymentDetails?.error_description ? (
            <Box marginTop="spacing.5">
              <Divider marginBottom="spacing.3" />
              <Text variant="body" size="small" weight="semibold" color="surface.text.gray.normal">
                {ERROR_DESCRIPTION_CONTENT_MAP[
                  paymentDetails?.error_description as keyof typeof ERROR_DESCRIPTION_CONTENT_MAP
                ]
                  ? ERROR_DESCRIPTION_CONTENT_MAP[
                      paymentDetails?.error_description as keyof typeof ERROR_DESCRIPTION_CONTENT_MAP
                    ]
                  : paymentDetails?.error_description}
              </Text>
            </Box>
          ) : null}
          {(paymentDetails?.status === 'refunded' || paymentIdRefundDetails.length > 0) &&
          paymentDetails.disputes.items.length === 0 ? (
            <Box marginTop="spacing.5">
              <Divider marginBottom="spacing.3" />
              {getRefundsOverviewDetails(paymentIdRefundDetails)}
            </Box>
          ) : null}
          {paymentDetails.disputes.items.length > 0 ? (
            <Box marginTop="spacing.5">
              <Divider marginBottom="spacing.3" />
              {getDisputesOverviewDetails(paymentDetails, viewDisputeCallback)}
            </Box>
          ) : null}
        </CardBody>
      </Card>
      <BoxContainer disableMarginTop>
        <CardWrapper enableBorderTopRadius>
          <Card padding="spacing.5" elevation="none">
            <CardBody>
              <RowsWrapper>
                <Box display="flex" justifyContent="space-between" paddingY="spacing.3">
                  <Text size="medium" weight="medium">
                    Gross amount
                  </Text>
                  <StyledAmountWrapper
                    data-testid="gross-amount"
                    type="positive"
                    fontSize={theme.typography.fonts.size[100]}
                  >
                    <BladeAmount
                      size="medium"
                      value={amount}
                      isAffixSubtle={false}
                      currency={currency || 'INR'}
                      color="feedback.text.positive.intense"
                    />
                  </StyledAmountWrapper>
                </Box>
                <DashedDivider />
                <Box position="relative" paddingY="spacing.3">
                  <CollapsibleContainer onClick={toggleDeductions}>
                    <Text size="medium" weight="medium">
                      Deductions{' '}
                      <StyledChevron>
                        {!isDeductionBreakdownOpen ? (
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
                  {isDeductionBreakdownOpen && (
                    <>
                      <Box
                        display="flex"
                        justifyContent="space-between"
                        paddingTop="spacing.3"
                        paddingLeft="spacing.3"
                      >
                        <Text>
                          {window.rzp_org?.business_name} platform fees{' '}
                          <Tooltip
                            type={
                              applicationDetails?.name ? 'partnerApplicationFees' : 'platformFees'
                            }
                            partnerApplicationName={applicationDetails?.name}
                            size="small"
                          />
                        </Text>
                        <BladeAmount
                          value={i18nifyConvertToMajorUnit(fee, currency)}
                          currency={currency || 'INR'}
                          isAffixSubtle={false}
                        />
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
                        <BladeAmount
                          value={i18nifyConvertToMajorUnit(tax, currency)}
                          currency={currency || 'INR'}
                          isAffixSubtle={false}
                        />
                      </Box>
                    </>
                  )}
                  <StyledAmountContainer>
                    <StyledAmountWrapper
                      type="negative"
                      fontSize={theme.typography.fonts.size[100]}
                    >
                      <BladeAmount
                        value={i18nifyConvertToMajorUnit(totalDeductions, currency)}
                        currency={currency || 'INR'}
                        isAffixSubtle={false}
                        color="feedback.text.negative.intense"
                      />
                    </StyledAmountWrapper>
                  </StyledAmountContainer>
                </Box>
                <Divider dividerStyle="solid" thickness="thick" variant="normal" />
                <Box display="flex" justifyContent="space-between" paddingY="spacing.3">
                  <Text size="medium" weight="medium">
                    Net amount
                  </Text>
                  <StyledAmountWrapper
                    data-testid="net-amount"
                    type="regular"
                    fontSize={theme.typography.fonts.size[100]}
                  >
                    <BladeAmount
                      value={i18nifyConvertToMajorUnit(netAmount, currency)}
                      currency={currency || 'INR'}
                      isAffixSubtle={false}
                    />
                  </StyledAmountWrapper>
                </Box>
              </RowsWrapper>
            </CardBody>
          </Card>
        </CardWrapper>
        <SectionFooter>
          {paymentDetails?.transaction?.settlement_id ? (
            <Text variant="body" size="small" weight="regular">
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
            <Text variant="body" size="small" weight="regular">
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
      </BoxContainer>
    </Box>
  );
}

const mapStateToProps = (state: any) => {
  return {
    user: state.session.user,
    settlementConfig: state.settlement.config,
    schedule: state.settlement.schedule,
    holidayList: state.settlement.holidayList,
  };
};

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) => {
  return bindActionCreators(
    {
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
