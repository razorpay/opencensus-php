import React from 'react';
import { ArrowRightIcon, Link } from '@razorpay/blade/components';
import { NavLink } from 'react-router-dom';
import styled from 'styled-components';
import { currencySymbols } from 'common/utils/rzp-utils';
import { fireCustomEvent } from 'merchant/components/Support/utils';
import { ROUTES, TEXT_CONTENT } from 'merchant/containers/Home/RTUX/MerchantOverview/constants';
import {
  IGetTodaySingleSettlementContent,
  IGetUpcommingSettlementData,
  IGetUpcommingSettlementOptions,
  IMultipleSettementFraction,
  ISettlementData,
  ISettlementConfig,
  ITodaySettlementData,
  SettlementStatusBadge,
  TodaySettlementKeys,
  UpcomingSettlementKeys,
  IAnalyticsProperties,
  ISettlementFeatures,
} from 'merchant/containers/Home/RTUX/MerchantOverview/types';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import { track } from 'merchant/widgets/utils';
import { getUser } from '@federated/apps/shell/commonStore';

// returns data in this format: February 7
export function getFormattedDateFromTimestamp(timestamp: number): string {
  const date = new Date(timestamp * 1000);
  return date.toLocaleString('en-US', {
    month: 'long',
    day: 'numeric',
  });
}

export const StyledImage = styled.img`
  height: 100%;
  position: absolute;
  right: 0;
  bottom: 0;
`;

export const isBlocked = (settlementFeatures: ISettlementFeatures): boolean => {
  return (
    settlementFeatures?.global_hold_config || settlementFeatures?.block || settlementFeatures?.hold
  );
};

// this utils transforms the settlement data for merchant overview widget and returns which settlement types to show
// Settlement
// ├── Today Settlement
// │   ├── Single Settlement
// │   └── Multiple Settlement
// ├── Upcomming Settlement
// │   ├── Upcomming Settlement Blocked/Paused
// │   │   ├── Blocked => FOH,SOH,MOH
// │   │   └── Paused => no next settlement time due to lack of activity
// │   └── Upcomming Settlement Skipped/On Track
// │       ├── Skipped => negative balance, balance less than 1rs
// │       └── On Track => else if we have next settlement amount and time.
// └── Previous Settlement
//     ├── Previous Settlement along with today/upcomming settlement
//     └── Only Previous Settlement
export const getSettlementConfig = (settlement: ISettlementData): ISettlementConfig => {
  const settlementRules = {
    shouldShowToday: false,
    isTodaySettlementPastProcessedSLA: false,
    isTodayMultipleSettlements: false,
    shouldShowPrevious: false,
    shouldShowUpcomming: false,
    shouldShowUpcommingBlock: false,
    shouldShowOnlyPreviousSettlement: false,
  };
  const { today, previous, upcoming_settlement } = settlement;
  // if today settlement is present we wont show upcomming settlement hence the if else condition
  if (today) {
    settlementRules.shouldShowToday = true;

    // if today is multiple settlement
    settlementRules.isTodayMultipleSettlements =
      today.title_key === TodaySettlementKeys.SETL_TODAY_MULTIPLE;

    // if today settlement is processed and past 8P.M.
    settlementRules.isTodaySettlementPastProcessedSLA =
      today.title_key === TodaySettlementKeys.SETL_TODAY_PROCESSED_AFTER_THAN_8PM;
  } else if (upcoming_settlement) {
    // show upcomming settlement skip and on track case
    settlementRules.shouldShowUpcomming = [
      UpcomingSettlementKeys.UPCOMING_SETL_ON_TRACK,
      UpcomingSettlementKeys.UPCOMING_SETL_SKIPPED_AMOUNT_GREATER_THAN_BALANCE,
      UpcomingSettlementKeys.UPCOMING_SETL_SKIPPED_AMOUNT_LESS_THAN_ONE,
    ].includes(upcoming_settlement.title_key);

    // show upcomming settlement blocked and paused case
    settlementRules.shouldShowUpcommingBlock = [
      UpcomingSettlementKeys.UPCOMING_SETL_BLOCK_FOH,
      UpcomingSettlementKeys.UPCOMING_SETL_BLOCK_MOH,
      UpcomingSettlementKeys.UPCOMING_SETL_BLOCK_SOH,
      UpcomingSettlementKeys.UPCOMING_SETL_SKIPPED_NO_NEXT_SETTLEMENT,
    ].includes(upcoming_settlement.title_key);
  }
  if (previous) {
    // in case of today multiple settlement we won't show previous settlement
    if (!settlementRules.isTodayMultipleSettlements) {
      settlementRules.shouldShowPrevious = true;
    }
    // if there is no today or upcomming settlement then just show previous settlement
    if (!today && !upcoming_settlement) {
      settlementRules.shouldShowOnlyPreviousSettlement = true;
    }
  }
  return settlementRules;
};

export const getMultipleSettlementBreakup = ({
  created_count,
  created_total_amount,
  delayed_count,
  delayed_total_amount,
  failed_count,
  failed_total_amount,
  processed_count,
  processed_total_amount,
}: ITodaySettlementData): IMultipleSettementFraction[] => {
  const statusProperties = [
    {
      status: SettlementStatusBadge.delayed,
      count: delayed_count,
      amount: delayed_total_amount,
    },
    {
      status: SettlementStatusBadge.failed,
      count: failed_count,
      amount: failed_total_amount,
    },
    {
      status: SettlementStatusBadge.created,
      count: created_count,
      amount: created_total_amount,
    },
    {
      status: SettlementStatusBadge.processed,
      count: processed_count,
      amount: processed_total_amount,
    },
  ];

  return statusProperties.filter(({ count }) => !!parseInt(`${count}`, 10));
};

export const getCommonLinkAnalyticsProperties = (analyticsProperties: Record<string, any>) => {
  const { screen, widgetId, settlementState, ...rest } = analyticsProperties ?? {};
  return {
    objectName: 'link',
    actionName: 'clicked' as const,
    screen,
    properties: {
      widgetId,
      actionBy: widgetId,
      title: settlementState,
      ...rest,
    },
  };
};

export const getUpcommingSettlementContent = ({
  title_key,
  next_settlement_time,
  settlement_currency,
  analyticsProperties,
}: IGetUpcommingSettlementOptions & IAnalyticsProperties): IGetUpcommingSettlementData => {
  const data: IGetUpcommingSettlementData = {
    status: SettlementStatusBadge.created,
    subheading: '',
    action: null,
  };

  const commonAnalyticsProperties = getCommonLinkAnalyticsProperties(analyticsProperties);

  switch (title_key) {
    case UpcomingSettlementKeys.UPCOMING_SETL_ON_TRACK: {
      data.status = SettlementStatusBadge.on_track;
      data.subheading = `To be processed by ${getFormattedDateFromTimestamp(next_settlement_time)}`;
      break;
    }
    case UpcomingSettlementKeys.UPCOMING_SETL_SKIPPED_AMOUNT_LESS_THAN_ONE: {
      data.status = SettlementStatusBadge.skipped;
      data.subheading = `Settlement amount must be more than ${currencySymbols[settlement_currency]}1 to be settled`;
      break;
    }
    case UpcomingSettlementKeys.UPCOMING_SETL_SKIPPED_AMOUNT_GREATER_THAN_BALANCE: {
      data.status = SettlementStatusBadge.skipped;
      data.subheading = TEXT_CONTENT.UPCOMING_SETL_SKIPPED_AMOUNT_GREATER_THAN_BALANCE;
      data.action = (
        <NavLink to={ROUTES_INFO.BALANCES}>
          <Link
            size="medium"
            icon={ArrowRightIcon}
            iconPosition="right"
            variant="button"
            onClick={() => {
              track({
                ...commonAnalyticsProperties,
                properties: {
                  ...commonAnalyticsProperties.properties,
                  action: ROUTES_INFO.BALANCES,
                  actionLabel: 'Add Funds',
                },
              });
            }}
          >
            Add Funds
          </Link>
        </NavLink>
      );
      break;
    }
    /* istanbul ignore next */
    default: {
      return data;
    }
  }
  return data;
};

export const getTodaySingleSettlementContent = (
  title_key: TodaySettlementKeys,
  analyticsProperties: IAnalyticsProperties['analyticsProperties'] = {},
): IGetTodaySingleSettlementContent => {
  const content: IGetTodaySingleSettlementContent = {
    status: SettlementStatusBadge.created,
    subheading: '',
    action: null,
  };

  const commonAnalyticsProperties = getCommonLinkAnalyticsProperties(analyticsProperties);

  switch (title_key) {
    case TodaySettlementKeys.SETL_TODAY_PROCESSED_BEFORE_THAN_8PM: {
      content.status = SettlementStatusBadge.processed;
      content.subheading = TEXT_CONTENT.TO_BE_PROCESSED_BY_8PM;
      break;
    }
    case TodaySettlementKeys.SETL_TODAY_PROCESSED_AFTER_THAN_8PM: {
      content.status = SettlementStatusBadge.processed;
      content.subheading = TEXT_CONTENT.PROCESSED_DEPOSITED;
      break;
    }
    case TodaySettlementKeys.SETL_TODAY_DELAYED: {
      content.status = SettlementStatusBadge.delayed;
      content.subheading = TEXT_CONTENT.TO_BE_PROCESSED_BY_8PM;
      break;
    }
    case TodaySettlementKeys.SETL_TODAY_CREATED: {
      content.status = SettlementStatusBadge.on_track;
      content.subheading = TEXT_CONTENT.TO_BE_PROCESSED_BY_8PM;
      break;
    }
    case TodaySettlementKeys.SETL_TODAY_FAIL_SOH: {
      content.status = SettlementStatusBadge.failed;
      content.subheading = TEXT_CONTENT.SOH_UPDATE_BANK_ACCOUNT;
      content.action = (
        <NavLink to={ROUTES_INFO.BANK_ACCOUNT_DETAILS}>
          <Link
            size="medium"
            icon={ArrowRightIcon}
            iconPosition="right"
            variant="button"
            onClick={() =>
              track({
                ...commonAnalyticsProperties,
                properties: {
                  ...commonAnalyticsProperties.properties,
                  action: ROUTES_INFO.BANK_ACCOUNT_DETAILS,
                  actionLabel: 'Update Bank details',
                },
              })
            }
          >
            Update Bank details
          </Link>
        </NavLink>
      );
      break;
    }
    case TodaySettlementKeys.SETL_TODAY_FAIL_RETRY_SLA_NOT_BREACHED: {
      content.status = SettlementStatusBadge.failed;
      content.subheading = TEXT_CONTENT.FAIL_SLA_NOT_BREACHED;
      break;
    }
    case TodaySettlementKeys.SETL_TODAY_FAIL_SLA_BREACHED: {
      content.status = SettlementStatusBadge.failed;
      content.subheading = TEXT_CONTENT.FAIL_SLA_BREACHED;
      content.action = (
        <Link
          size="medium"
          icon={ArrowRightIcon}
          iconPosition="right"
          variant="button"
          onClick={() => {
            track({
              ...commonAnalyticsProperties,
              properties: {
                ...commonAnalyticsProperties.properties,
                action: 'create-ticket',
                actionLabel: 'Contact Support',
              },
            });
            CreateTicketEmitter.emit('create-ticket', 'tickets');
          }}
        >
          Contact Support
        </Link>
      );
      break;
    }
    /* istanbul ignore next */
    default: {
      return content;
    }
  }
  return content;
};

export const upcommingSettlementBlockedContent = (
  title: string,
  analyticsProperties: IAnalyticsProperties['analyticsProperties'],
) => {
  const commonAnalyticsProperties = getCommonLinkAnalyticsProperties(analyticsProperties);
  switch (title) {
    case 'UPCOMING_SETL_BLOCK_FOH':
      return {
        status: SettlementStatusBadge.blocked,
        subheading: TEXT_CONTENT.FOH_UPCOMMING_BLOCK,
        action: (
          <Link
            icon={ArrowRightIcon}
            iconPosition="right"
            variant="button"
            size="small"
            onClick={() => {
              CreateTicketEmitter.emit('create-ticket', 'tickets');
              track({
                ...commonAnalyticsProperties,
                properties: {
                  ...commonAnalyticsProperties.properties,
                  action: 'create-ticket',
                  actionLabel: 'Contact Support',
                },
              });
            }}
          >
            Contact Support
          </Link>
        ),
      };
    case 'UPCOMING_SETL_BLOCK_SOH':
      return {
        status: SettlementStatusBadge.blocked,
        subheading: TEXT_CONTENT.SOH_UPCOMMING_BLOCK,
        action: (
          <NavLink to={ROUTES_INFO.BANK_ACCOUNT_DETAILS}>
            <Link
              icon={ArrowRightIcon}
              iconPosition="right"
              variant="button"
              size="small"
              onClick={() =>
                track({
                  ...commonAnalyticsProperties,
                  properties: {
                    ...commonAnalyticsProperties.properties,
                    action: ROUTES_INFO.BANK_ACCOUNT_DETAILS,
                    actionLabel: 'Update Bank details',
                  },
                })
              }
            >
              Update Bank details
            </Link>
          </NavLink>
        ),
      };
    case 'UPCOMING_SETL_BLOCK_MOH':
      return {
        status: SettlementStatusBadge.blocked,
        subheading: TEXT_CONTENT.MOH_UPCOMMING_BLOCK,
        action: (
          <Link
            icon={ArrowRightIcon}
            iconPosition="right"
            variant="button"
            size="small"
            onClick={() => {
              track({
                ...commonAnalyticsProperties,
                properties: {
                  ...commonAnalyticsProperties.properties,
                  action: 'create-ticket',
                  actionLabel: 'Contact Support',
                },
              });
              CreateTicketEmitter.emit('create-ticket', 'tickets');
            }}
          >
            Contact Support
          </Link>
        ),
      };
    case 'UPCOMING_SETL_SKIPPED_NO_NEXT_SETTLEMENT':
      return {
        status: SettlementStatusBadge.paused,
        subheading: TEXT_CONTENT.UPCOMMING_SKIP_NO_NEXT,
        action: (
          <NavLink to={ROUTES.SETTLEMENT}>
            <Link
              icon={ArrowRightIcon}
              iconPosition="right"
              variant="button"
              size="small"
              onClick={() =>
                track({
                  ...commonAnalyticsProperties,
                  properties: {
                    ...commonAnalyticsProperties.properties,
                    action: ROUTES.SETTLEMENT,
                    actionLabel: 'View all settlements',
                  },
                })
              }
            >
              View all settlements
            </Link>
          </NavLink>
        ),
      };
    default:
      return {};
  }
};

export const getSettlementStatusImage = (status: string) => {
  switch (status.toLowerCase()) {
    case SettlementStatusBadge.on_track:
      return {
        name: 'settlement-on-track.gif',
        duration: 7000,
        styles: { width: 108, height: 108 },
      };
    case SettlementStatusBadge.delayed:
      return {
        name: 'settlement-delayed.gif',
        duration: 7000,
        styles: { width: 108, height: 108 },
      };
    case SettlementStatusBadge.failed:
      return {
        name: 'settlement-failed.gif',
        duration: 7000,
        styles: { width: 108, height: 108 },
      };
    case SettlementStatusBadge.processed:
      return {
        name: 'settlement-processed.gif',
        duration: 7000,
        styles: { width: 108, height: 108 },
      };
    case SettlementStatusBadge.blocked:
      return {
        name: 'settlement-blocked.gif',
        duration: 7000,
        styles: { width: 108, height: 108 },
      };
    case SettlementStatusBadge.paused:
      return {
        name: 'settlement-paused.gif',
        duration: 7000,
        styles: { width: 108, height: 108 },
      };
    default:
      return null;
  }
};

export const fireContactSupportFohEvent = (ticketId: string) => {
  fireCustomEvent({
    event: 'open-contact-support',
    data: {
      contactNumber: '08068838200',
      ticketId: ticketId,
    },
  });
};

export const RISK_FOH_TAGS = [
  'MS_risk_review_onhold',
  'Risk_review_onhold',
  'SC_risk_review_onhold',
  'XRisk_AML_onhold',
  'XRisk_onhold',
];

export const RISK_DISABLE_TAGS = [
  'MS_risk_review_disable_live',
  'Risk_review_disable_live',
  'SC_risk_review_disable_live',
  'XRisk_AML_Disable_live',
  'XRisk_disable_live',
];

const hasTags = (userTags: string[] | undefined, tagsList: string[]): boolean => {
  return userTags?.some?.((tag) => tagsList?.includes?.(tag)) ?? false;
};

export const isRiskFoh = (): boolean => {
  const user = getUser();
  return hasTags(user?.tags, RISK_FOH_TAGS) && !!user.merchant?.hold_funds;
};

export const isRiskDisabled = (): boolean => {
  const user = getUser();
  return hasTags(user?.tags, RISK_DISABLE_TAGS) && !user.live;
};

export const showContactSupport = (ticketStatus) => {
  return (isRiskFoh() || isRiskDisabled()) && ticketStatus !== 'Closed';
};

export const blockTicketCreationFoh = (user, fohTicketStatus) => {
  const ticketStatus =
    fohTicketStatus !== 'Closed' && fohTicketStatus !== 'Auto Closed' && fohTicketStatus !== '';
  const isRiskFohMerchant =
    user.tags?.some?.((tag) => RISK_FOH_TAGS.includes(tag)) && user.merchant?.hold_funds;
  const isRiskDisableMerchant =
    user.tags?.some?.((tag) => RISK_DISABLE_TAGS.includes(tag)) && !user.live;
  return ticketStatus && (isRiskFohMerchant || isRiskDisableMerchant);
};
