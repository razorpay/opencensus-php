import { convertToMajorUnit, formatNumber } from '@razorpay/i18nify-js/currency';
import moment from 'moment';

import { ANALYTICS } from 'common/constant';
import { analyticsTrackWithUserInfo, analyticsTrack } from 'common/utils/analytics';
import { titleCase } from 'common/utils/rzp-utils';
import { SETTLEMENT_INFO } from 'merchant/views/Settlements/v3/constants/info';
import {
  AlertInterface,
  SettlementDetailViewInterface,
  SettlementFailedStatus,
  SettlementInfoInterface,
  SettlementPropsInterface,
  SettlementStatus,
  SettlementStatusIcons,
  TimelineJourneyInterface,
} from 'merchant/views/Settlements/v3/typings';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import { getHumanReadableTimestamp } from 'merchant/views/Transactions/v2/Payments/components/Timeline/utils';
import { isOrgFeatureExist } from 'merchant/models/User';

const FailedBannerConfig = {
  FAILED: {
    heading: 'Contact support to receive failed settlement',
    description:
      "Your previous settlement could not be processed as we've encountered a few issues",
    action: (settlement) => ({
      actions: {
        primary: {
          onClick: () => {
            CreateTicketEmitter.emit('create-ticket', 'tickets');
            analyticsTrackWithUserInfo({
              objectName: 'Create Ticket',
              actionName: 'Clicked v2',
              screen: 'Settlements',
              properties: {
                page: 'Details View',
                settlements_experiment_name: 'v2',
                settlementId: settlement.id,
                settlementStatus: settlement.status,
                sessionId: window?.session_id ? window.session_id : undefined,
                title: 'Contact support to receive failed settlement',
              },
            });
          },
          text: 'Contact support',
        },
      },
    }),
  },
  RETRYING: {
    heading: 'Your failed settlement is being automatically retried',
    description:
      "We encountered a few issues with your given bank account while processing your settlement. We'll share an update soon",
  },
};

export const FailedSettlementInfo = {
  [SettlementFailedStatus.SOH_HOLD]: {
    title: 'Your settlements are temporary on-hold.',
    subtitle: 'Please update your Bank account to unblock your settlements.',
  },
  [SettlementFailedStatus.FOH_HOLD]: {
    title: 'Contact support to resume settlements for your account.',
    subtitle:
      'Your account Your settlements are on-hold as we’ve noticed unusual activity in your account.',
  },
  [SettlementFailedStatus.RETRYING]: {
    title: FailedBannerConfig.RETRYING.heading,
    subtitle: FailedBannerConfig.RETRYING.description,
  },
  [SettlementFailedStatus.FAILED]: {
    title: FailedBannerConfig.FAILED.heading,
    subtitle: FailedBannerConfig.FAILED.description,
  },
};

export const getSettlementInfo = ({
  settlement,
}: {
  settlement: SettlementPropsInterface;
}): SettlementInfoInterface[] => {
  return [...SETTLEMENT_INFO].reduce((accumulator, each) => {
    accumulator.push({
      ...each,
      value: settlement[each.id],
    });
    return accumulator;
  }, [] as SettlementInfoInterface[]);
};

export const getTimelineJourneyDetails = ({
  created_at,
  status,
}: Pick<SettlementPropsInterface, 'created_at' | 'status'>): TimelineJourneyInterface[] => {
  const journey: TimelineJourneyInterface[] = [
    {
      id: SettlementStatus.CREATED,
      status: 'Created',
      timeline: moment.unix(created_at).format('llll'),
    },
  ];
  if (['failed', 'processed'].indexOf(status) > -1) {
    journey.push({
      id: status,
      status: titleCase(status),
    });
  }
  return journey;
};

function isPastSettlementTime(unixTimestamp) {
  const istOffset = 5.5 * 60 * 60 * 1000;
  const currentDate = new Date();
  const initialDate = new Date(unixTimestamp * 1000 + istOffset);

  initialDate.setHours(23, 0, 0, 0);

  return currentDate > initialDate;
}

function getSettlementProcessedTime(unixTimestamp) {
  const istOffset = 5.5 * 60 * 60 * 1000;
  const currentDate = new Date();
  const initialDate = new Date(unixTimestamp * 1000 + istOffset);

  if (currentDate < initialDate) {
    return `Processed today`;
  } else {
    const daysPassed = Math.floor((+currentDate - +initialDate) / (24 * 60 * 60 * 1000));
    return `Processed on ${initialDate.toDateString()} (${daysPassed} days ago)`;
  }
}

export const getTimelineJourneyDetailsRevamp = ({
  settlement,
  settlementConfig,
  user,
}: any): any[] => {
  const { created_at, status, amount, utr } = settlement;
  const now = moment();
  const breachTime = moment.unix(created_at).add(7, 'hours');
  const shouldHideSettlementTime = isOrgFeatureExist('hide_settlement_time');
  const isVASMerchant = !(user.isOrgRZP || user.isOrgCurlec);

  let failedType;
  const {
    merchant: { hold_funds, currency: merchantCurrency },
  } = user;
  const { global_hold_config, hold } = settlementConfig?.data?.config?.features || {};

  let netAmount;
  try {
    netAmount = formatNumber(convertToMajorUnit(amount, { currency: merchantCurrency }), {
      currency: merchantCurrency,
    });
  } catch (error) {
    netAmount = '--';
    analyticsTrack({
      objectName: ANALYTICS.OBJECT.I18N,
      actionName: ANALYTICS.ACTION.CURRENCY,
      screen: ANALYTICS.SCREEN.DASHBOARD,
      properties: {
        input: `amount: ${amount}, currency: ${merchantCurrency}`,
        error: `${error}`,
      },
    });
  }

  if (status !== SettlementStatus.FAILED) {
    failedType = '';
  } else if (global_hold_config?.status || hold_funds) {
    failedType = SettlementFailedStatus.FOH_HOLD;
  } else if (hold?.status) {
    failedType = SettlementFailedStatus.SOH_HOLD;
  } else if (now.isBefore(breachTime)) {
    failedType = SettlementFailedStatus.RETRYING;
  } else {
    failedType = SettlementFailedStatus.FAILED;
  }

  const journey: any[] = [
    {
      status,
      title: 'Settlement created',
      subtitle: getHumanReadableTimestamp(created_at, true),
      icon: SettlementStatusIcons.DONE,
    },
  ];
  if (status === SettlementStatus.CREATED) {
    journey.push(
      {
        status,
        title: 'Settlement processed',
        subtitle: 'To be processed today',
        icon: SettlementStatusIcons.IN_PROGRESS,
      },
      {
        status,
        title: 'Money to be deposited in bank account',
        subtitle: `Net amount: ${netAmount}`,
        secondarySubtitle: `To be deposited latest by ${
          shouldHideSettlementTime ? '' : '11:00 pm'
        }, today`,
        icon: SettlementStatusIcons.IN_PROGRESS,
      },
    );
  }

  if (status === SettlementStatus.PROCESSED) {
    const shouldHaveSettled = isPastSettlementTime(created_at);
    journey.push({
      status,
      title: 'Settlement processed',
      subtitle: getSettlementProcessedTime(created_at),
      mutedInfo: `We have successfully processed the settlement. It may take 2-3 hours for the funds to reflect in your bank account. If the money has still not been deposited after this time, please contact your bank using the UTR number (${
        utr ?? '-'
      }).`,
      icon: SettlementStatusIcons.DONE,
    });
    if (!isVASMerchant) {
      journey.push({
        status,
        title: shouldHaveSettled
          ? 'Money deposited in bank account'
          : 'Money to be deposited in bank account',
        subtitle: `Net amount: ${netAmount ?? '-'}`,
        secondarySubtitle: shouldHaveSettled
          ? `UTR number: ${utr ?? '-'}`
          : 'To be deposited latest by 11:00 pm, today',
        icon: shouldHaveSettled ? SettlementStatusIcons.DONE : SettlementStatusIcons.IN_PROGRESS,
      });
    }
  }

  if (status === SettlementStatus.FAILED) {
    journey.push({
      status,
      title: 'Settlement failed',
      icon: SettlementStatusIcons.FAILED,
      failedType,
    });
  }
  return journey;
};

export const getFailedAlert = ({
  settlement,
}: Pick<SettlementDetailViewInterface, 'settlement'>): AlertInterface => {
  const { status, created_at } = settlement;
  const response = {
    shouldShouldFailedAlert: false,
  };
  if (status === 'failed') {
    const now = moment();
    const breachTime = moment.unix(created_at).add(7, 'hours');
    if (now.isBefore(breachTime)) {
      return {
        bannerConfig: { ...FailedBannerConfig.RETRYING },
        shouldShouldFailedAlert: true,
      };
    }
    return {
      bannerConfig: { ...FailedBannerConfig.FAILED },
      shouldShouldFailedAlert: true,
    };
  }
  return response;
};
