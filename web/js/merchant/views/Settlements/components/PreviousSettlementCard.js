import React from 'react';
import { Badge } from '@razorpay/blade/components';

import Amount from 'common/ui/Amount';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';

import SettlementCard from './SettlementCard';
import { FlexBetween, CardWrapper } from './styledUtils';
import { BADGE_INFO, HEADING_INFO, SETTLEMENT_STATUS } from './utils';

const PreviousSettlementCard = ({ settlementsList, currency }) => {
  const amount = settlementsList?.[0]?.amount || 0;

  let badge;

  switch (settlementsList?.[0]?.status?.toLowerCase()) {
    case SETTLEMENT_STATUS.CREATED:
      badge = (
        <span>
          <Badge size="medium" color="notice">
            Created
          </Badge>
          <PopoverComponent align="top" theme="dark">
            <PopoverBody>{BADGE_INFO.CREATED}</PopoverBody>
          </PopoverComponent>
        </span>
      );
      break;
    case SETTLEMENT_STATUS.PROCESSED:
      badge = (
        <span>
          <Badge size="medium" color="positive">
            Processed
          </Badge>
          <PopoverComponent align="top" theme="dark">
            <PopoverBody>{BADGE_INFO.PROCESSED}</PopoverBody>
          </PopoverComponent>
        </span>
      );
      break;
    case SETTLEMENT_STATUS.FAILED:
      badge = (
        <span>
          <Badge size="medium" color="negative">
            Failed
          </Badge>
          <PopoverComponent align="top" theme="dark">
            <PopoverBody>{BADGE_INFO.FAILED}</PopoverBody>
          </PopoverComponent>
        </span>
      );
      break;
    default:
      break;
  }

  const customCardStyle = `
    padding-top: 0;
    padding-bottom: 0;
  `;

  const content = (
    <FlexBetween>
      {settlementsList?.[0] ? (
        <>
          {currency ? (
            <Amount
              aria-label="amount"
              value={amount}
              currency={currency}
              className="amount-current-balance"
            />
          ) : (
            <PlaceholderLoader />
          )}

          {badge}
        </>
      ) : (
        'NA'
      )}
    </FlexBetween>
  );

  return (
    <CardWrapper>
      <SettlementCard
        heading="Previous settlement"
        headingInfo={HEADING_INFO.PREVIOUS_SETTLEMENT}
        content={content}
        customCardStyle={customCardStyle}
      />
    </CardWrapper>
  );
};

export default PreviousSettlementCard;
