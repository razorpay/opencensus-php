import React from 'react';
import Time from 'common/ui/Time';
import moment from 'moment';

const messageFactory = {
  getEnableScheduledSettlements: () => ({
    heading: 'Get your settlements on the same day automatically!',
    description:
      'You can get daily settlements on working days automatically without having to visit the dashboard each time.',
    image: 'early-settlement-light-blue',
    showEnableNowButton: true,
    hideRightImages: true,
  }),
  getSettlementWillBeSkipped: () => ({
    heading: 'Scheduled settlement will be skipped',
    description:
      'Since you do not have enought current balance, your 5pm scheduled settlement will be skipped',
    image: 'notification',
  }),
  getBankHolidayUpcoming: ({ date, description }) => ({
    heading: 'A bank holiday is coming up. Settle now!',
    description: (
      <>
        Please note that{' '}
        <strong>{<Time value={moment(date, 'DD/MM/YYYY').unix()} format="MMMM Do" />}</strong> is a
        bank holiday {description}. Get money settled to your bank account now with Ondemand
        Settlements, available even during bank holidays and weekends
      </>
    ),
    image: 'calendar',
  }),
  getSettleNonBankingHoursNudge: () => ({
    heading: 'Get your money during non-banking hours and weekends!',
    description:
      'With Ondemand Settlements, get money settled to your bank account in a few seconds, even during non-banking hours and weekends',
    image: 'message-bubble',
  }),
  getBankHolidayToday: ({ date, description }) => ({
    heading: 'Get money settled on a bank holiday ',
    description: (
      <>
        Today {<Time value={moment(date, 'DD/MM/YYYY').unix()} format="MMMM Do" />} is a bank
        holiday({description}). Get money settled to your bank account now with Ondemand
        Settlements.
      </>
    ),
    image: 'calendar',
  }),
};

export default messageFactory;
