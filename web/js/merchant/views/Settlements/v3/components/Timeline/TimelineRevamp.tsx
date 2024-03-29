import { Box, ChevronRightIcon, Heading, Link, Text } from '@razorpay/blade/components';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import { SettlementFailedStatus } from 'merchant/views/Settlements/v3/typings';
import {
  trackSettlmentDetailsContactSupport,
  trackSettlmentDetailsUpdateBankAccount,
} from 'merchant/views/Settlements/v3/utils/common';
import {
  FailedSettlementInfo,
  getTimelineJourneyDetailsRevamp,
} from 'merchant/views/Settlements/v3/utils/settlementInfo';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import {
  IconBackground,
  StyledGradientBox,
  StyledJourneyStatus,
  StyledText,
  StyledTimelineContainer,
  getStatusIcon,
} from 'merchant/views/Transactions/v2/Payments/components/Timeline/styled';
import React from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import { StyledSettlementJourneyMetadata, StyledTimelineRevamp } from './styled';

const getFailedSettlementInfo = (journeyPoint): JSX.Element | null => {
  const { failedType } = journeyPoint;
  const { title, subtitle } = FailedSettlementInfo[failedType] ?? {};

  if (!failedType) {
    return null;
  }
  return (
    <>
      <StyledGradientBox>
        <Text size="small" weight="semibold">
          {title}
        </Text>
        <Text size="small" color="surface.text.gray.subtle" weight="regular">
          {subtitle}
        </Text>
      </StyledGradientBox>
      <Box paddingTop="spacing.2" paddingBottom="spacing.2">
        {[SettlementFailedStatus.FOH_HOLD, SettlementFailedStatus.FAILED].includes(failedType) ? (
          <Link
            size="small"
            icon={ChevronRightIcon}
            iconPosition="right"
            variant="button"
            onClick={() => {
              trackSettlmentDetailsContactSupport();
              CreateTicketEmitter.emit('create-ticket', 'tickets');
            }}
          >
            Contact support
          </Link>
        ) : failedType === SettlementFailedStatus.SOH_HOLD ? (
          <NavLink to={ROUTES_INFO.BANK_ACCOUNT_DETAILS}>
            <Link
              size="small"
              icon={ChevronRightIcon}
              iconPosition="right"
              variant="button"
              onClick={trackSettlmentDetailsUpdateBankAccount}
            >
              Update Bank account
            </Link>
          </NavLink>
        ) : null}
      </Box>
    </>
  );
};

const getSettlementJourneyMeta = (journeyPoint): JSX.Element => {
  return (
    <StyledSettlementJourneyMetadata>
      {journeyPoint.subtitle && (
        <Text size="small" color="surface.text.gray.subtle" weight="regular">
          {journeyPoint.subtitle}
        </Text>
      )}
      {journeyPoint.secondarySubtitle && (
        <Text size="small" color="surface.text.gray.subtle" weight="regular">
          {journeyPoint.secondarySubtitle}
        </Text>
      )}
      {journeyPoint.mutedInfo && (
        <Text
          color="surface.text.gray.muted"
          weight="semibold"
          size="small"
          marginBottom="spacing.8"
          marginTop="spacing.4"
        >
          {journeyPoint.mutedInfo}
        </Text>
      )}
      {getFailedSettlementInfo(journeyPoint)}
    </StyledSettlementJourneyMetadata>
  );
};

const Timeline = ({ settlement, settlementConfig, user }): JSX.Element => {
  const timelineJourney = getTimelineJourneyDetailsRevamp({
    settlement,
    settlementConfig,
    user,
  });

  return (
    <StyledTimelineRevamp>
      <Box padding="spacing.5" paddingBottom={'spacing.0'} paddingTop="spacing.7">
        <Heading weight="semibold" size="small">
          Timeline
        </Heading>
      </Box>
      <Box
        display="flex"
        alignItems="flex-start"
        gap="spacing.4"
        padding="spacing.7"
        testID="timeline"
      >
        <StyledTimelineContainer style={{ flex: '1' }}>
          <Box display="flex" flexDirection="column">
            {timelineJourney.map((each, index) => (
              <Box
                display="flex"
                flexDirection="column"
                position="relative"
                key={`${each.status}_${index}`}
                marginTop="30px"
              >
                <Box position="absolute" top="-25px" left="-9px">
                  <IconBackground status={each.icon}>{getStatusIcon(each.icon)}</IconBackground>
                </Box>
                <StyledJourneyStatus>
                  <StyledText>{each.title}</StyledText>
                </StyledJourneyStatus>
                {getSettlementJourneyMeta(each)}
              </Box>
            ))}
          </Box>
        </StyledTimelineContainer>
      </Box>
    </StyledTimelineRevamp>
  );
};

const mapStateToProps = (state) => ({
  settlement: state.settlement.settlement,
  user: state.session.user,
  settlementConfig: state.settlement.config,
});

export default connect(mapStateToProps, null)(Timeline);
