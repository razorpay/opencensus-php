import React from 'react';
import { Badge, ClockIcon } from '@razorpay/blade/components';
import { getCurrencySymbol as i18nifyGetCurrencySymbol } from '@razorpay/i18nify-js/currency';
import moment from 'moment';

import Amount from 'common/ui/Amount';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import { getFormattedAmount } from 'common/utils/rzp-utils';

import SettlementCard from './SettlementCard';
import { FlexBetween, CardFooterIcon, TextFooter } from './styledUtils';
import { BADGE_INFO, HEADING_INFO, SETTLEMENT_SLA_IN_HOURS, SETTLEMENT_STATUS } from './utils';

const InitiatedSettlementStatuses = [SETTLEMENT_STATUS.CREATED, SETTLEMENT_STATUS.INITIATED];

const SettlementDueTodayCard = ({ settlementsList, settlementConfig, currency = 'INR' }) => {
  const initiatedSettlements = settlementsList?.filter((setl) =>
    InitiatedSettlementStatuses.includes(setl?.status?.toLowerCase()),
  );
  const dailySettlement = settlementConfig?.data?.config?.preferences?.daily_settlement;
  const delayedInitiate = settlementConfig?.data?.config?.initate_types?.delayed?.enable;
  const isDelayedSettlement = dailySettlement || delayedInitiate;

  const delayedSettlements = initiatedSettlements?.filter(
    (setl) => moment().diff(moment.unix(setl.created_at), 'hours') > SETTLEMENT_SLA_IN_HOURS,
  );

  const totalTransferAmount = initiatedSettlements?.reduce((total, setl) => total + setl.amount, 0);
  const delayedTransferAmount = delayedSettlements?.reduce((total, setl) => total + setl.amount, 0);
  const settlementETA = moment
    .unix(initiatedSettlements?.[0]?.created_at)
    .add(SETTLEMENT_SLA_IN_HOURS, 'hours')
    .format('DD MMM, h:mm A');
  const currencySym = i18nifyGetCurrencySymbol(currency);

  const content = (
    <FlexBetween>
      <Amount
        aria-label="amount"
        value={totalTransferAmount}
        currency={currency}
        className="amount-current-balance"
      />
      {delayedTransferAmount > 0 && !isDelayedSettlement ? (
        <span>
          <Badge size="medium" color="negative">
            {`${currencySym} ${getFormattedAmount(delayedTransferAmount)} Delayed`}
          </Badge>
          <PopoverComponent align="top" theme="dark">
            <PopoverBody>{BADGE_INFO.DELAYED}</PopoverBody>
          </PopoverComponent>
        </span>
      ) : (
        initiatedSettlements?.length > 0 && (
          <span>
            <Badge size="medium" color="notice">
              Created
            </Badge>
            <PopoverComponent align="top" theme="dark">
              <PopoverBody>{BADGE_INFO.CREATED}</PopoverBody>
            </PopoverComponent>
          </span>
        )
      )}
    </FlexBetween>
  );

  const footer =
    !isDelayedSettlement &&
    initiatedSettlements?.length > 0 &&
    (initiatedSettlements?.length > 1 ? (
      <TextFooter>
        <span>
          {initiatedSettlements.length} settlements to be processed by {settlementETA}
        </span>
      </TextFooter>
    ) : (
      <TextFooter>
        <CardFooterIcon>
          <ClockIcon color="currentColor" size="small" />
        </CardFooterIcon>
        <span>To be processed by {settlementETA}</span>
      </TextFooter>
    ));

  return (
    <SettlementCard
      heading="Settlement due today"
      headingInfo={HEADING_INFO.SETTLEMENT_DUE_TODAY}
      content={content}
      footer={footer}
    />
  );
};

export default SettlementDueTodayCard;
