import React from 'react';
import { Badge, HelpCircleIcon, ClockIcon } from '@razorpay/blade/components';
import { getCurrencySymbol as i18nifyGetCurrencySymbol } from '@razorpay/i18nify-js/currency';
import moment from 'moment/moment';

import Amount from 'common/ui/Amount';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';

import SettlementCard from './SettlementCard';
import { FlexBetween, CardWrapper, CardFooterIcon, TextFooter } from './styledUtils';
import { BADGE_INFO, HEADING_INFO } from './utils';

const UpcomingSettlementCard = ({ next_settlement, settlementConfig, currency }) => {
  const no_settlement = next_settlement?.no_settlement;

  const isBlock = settlementConfig?.data?.config?.features?.block?.status;
  const isOnTemporaryHold = settlementConfig?.data?.config?.features?.hold?.status;
  const isOnHold = no_settlement?.on_hold;
  const currencySym = i18nifyGetCurrencySymbol(currency);

  const showBlockedBadge = isBlock || isOnHold || isOnTemporaryHold;

  let footer, badge;

  if (showBlockedBadge) {
    badge = (
      <span>
        <Badge size="medium" icon={HelpCircleIcon} color="negative">
          Blocked
        </Badge>
        <PopoverComponent align="top" theme="dark">
          <PopoverBody>{BADGE_INFO.BLOCKED}</PopoverBody>
        </PopoverComponent>
      </span>
    );
  }

  if (next_settlement?.next_settlement_time) {
    const nextSettlementTime = moment.unix(next_settlement?.next_settlement_time);

    const currentTime = moment();

    footer = moment(nextSettlementTime).isBefore(currentTime) ? (
      <TextFooter>
        <CardFooterIcon>
          <ClockIcon color="currentColor" size="small" />
        </CardFooterIcon>
        <span>About to start</span>
      </TextFooter>
    ) : (
      <TextFooter>
        <CardFooterIcon>
          <ClockIcon color="currentColor" size="small" />
        </CardFooterIcon>
        <span className="pr-5">
          To be processed on {nextSettlementTime.format('DD MMM, h:mm A')}
        </span>
      </TextFooter>
    );
  }

  if (next_settlement?.next_settlement_time && next_settlement?.settlement_amount < 100) {
    footer = (
      <TextFooter>
        <span>Amount more than {currencySym}1 is settled </span>
      </TextFooter>
    );
  }

  const customCardStyle = `
    padding-top: 0;
    padding-bottom: 0;
  `;

  const content = (
    <FlexBetween>
      {next_settlement?.next_settlement_time ? (
        <Amount
          aria-label="amount"
          value={next_settlement?.settlement_amount}
          currency={currency}
          className="amount-current-balance"
        />
      ) : (
        'NA'
      )}
      {badge}
    </FlexBetween>
  );

  return (
    <CardWrapper>
      <SettlementCard
        heading="Upcoming settlement"
        headingInfo={HEADING_INFO.UPCOMING_SETTLEMENT}
        content={content}
        footer={footer}
        customCardStyle={customCardStyle}
      />
    </CardWrapper>
  );
};

export default UpcomingSettlementCard;
