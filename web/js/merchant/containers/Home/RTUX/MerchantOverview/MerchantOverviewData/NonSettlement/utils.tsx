import React from 'react';
import { NavLink } from 'react-router-dom';
import { Button } from '@razorpay/blade/components';

import { TEXT_CONTENT } from 'merchant/containers/Home/RTUX/MerchantOverview/constants';
import {
  IAnalyticsProperties,
  IGetNonSettlementCardContent,
  IGetNonSettlementCardContentOptions,
} from 'merchant/containers/Home/RTUX/MerchantOverview/types';
import SettlementCycle from 'merchant/views/Settlements/components/SettlementScheduleV2';
import { track } from 'merchant/widgets/utils';
import { getCommonLinkAnalyticsProperties } from '../Settlement/utils';

export function getNonSettlementCardContent({
  is_transacted,
  settlement_schedule,
  openModal,
  analyticsProperties,
}: IGetNonSettlementCardContentOptions & IAnalyticsProperties): IGetNonSettlementCardContent {
  const title = is_transacted
    ? TEXT_CONTENT.NO_SETTLEMENT_TRANSACTED
    : TEXT_CONTENT.NO_SETTLEMENT_NOT_TRANSACTED;

  const subtitle = is_transacted
    ? `We're on-track to deposit the payments in your bank account by ${settlement_schedule}, as per your settlement cycle`
    : TEXT_CONTENT.NO_SETTLEMENT_NOT_TRANSACTED_SUBHEADING;

  const commonAnalyticsProperties = getCommonLinkAnalyticsProperties(analyticsProperties);

  const action = is_transacted ? (
    <>
      <Button
        size="small"
        onClick={() => {
          track({
            ...commonAnalyticsProperties,
            properties: {
              ...commonAnalyticsProperties.properties,
              action: 'settlement_cycle_modal',
              actionLabel: 'View settlement cycle',
            },
          });
          openModal({
            size: 'medium',
            component: <SettlementCycle />,
          });
        }}
      >
        View settlement cycle
      </Button>
      <Button
        size="small"
        variant="tertiary"
        onClick={() => {
          track({
            ...commonAnalyticsProperties,
            properties: {
              ...commonAnalyticsProperties.properties,
              action: 'https://razorpay.com/settlement',
              actionLabel: 'View settlement guide',
            },
          });
          window.open('https://razorpay.com/settlement', '_blank', 'rel=noopener noreferrer');
        }}
      >
        View settlement guide
      </Button>
    </>
  ) : (
    <NavLink to="/paymentlinks">
      <Button
        size="small"
        onClick={() => {
          track({
            ...commonAnalyticsProperties,
            properties: {
              ...commonAnalyticsProperties.properties,
              actionLabel: 'Collect payments',
              action: '/paymentlinks',
            },
          });
        }}
      >
        Collect payments
      </Button>
    </NavLink>
  );

  const illustration = is_transacted ? 'clap.gif' : 'cash-rain.gif';

  return {
    illustration,
    title,
    subtitle,
    action,
  };
}
