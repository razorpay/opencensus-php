import { titleCase, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { SETTLEMENT_INFO } from 'merchant/views/Settlements/v3/constants/info';
import {
  AlertInterface,
  SettlementDetailViewInterface,
  SettlementInfoInterface,
  SettlementPropsInterface,
  TimelineJourneyInterface,
} from 'merchant/views/Settlements/v3/typings';
import { CreateTicketEmitter } from 'merchant/views/TicketSupport/utils';
import moment from 'moment';
import { analyticsTrack } from 'common/utils/analytics';

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
            analyticsTrack({
              objectName: 'Merchant clicked',
              actionName: 'Contact Support on the banner',
              screen: 'Settlements',
              properties: {
                ...getCommonAnalyticsProperties(window.rzp_user),
                page: 'Details View',
                settlements_experiment_name: 'v2',
                settlementId: settlement.id,
                settlementStatus: settlement.status,
                sessionId: window?.session_id ? window.session_id : undefined,
              },
            });
          },
          text: 'Contact support',
        },
      },
    }),
  },
  RETRYING: {
    heading: 'Your failed settlement is being automatically retried ',
    description:
      "We encountered a few issues with your given bank account while processing your settlement. We'll share an update soon",
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
      id: 'created',
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
