import React from 'react';
import { TrendingUpIcon, TrendingDownIcon } from '@razorpay/blade/components';
import { Card, CardCenter, CardFooter, CardHeader } from './styled';
import moment from 'moment';
import { WEEKS_MAP } from 'merchant/views/PaymentMetrics/constants';

import LoadingError from './LoadingError';
import { ComparisonGraphs } from 'merchant/views/PaymentMetrics/types';

const dow = moment().day();

const UpIcon = () => {
  return <TrendingUpIcon color="feedback.icon.positive.lowContrast" size="medium" />;
};
const DownIcon = () => {
  return <TrendingDownIcon color="feedback.icon.notice.lowContrast" size="medium" />;
};

const CRComparison = ({
  crData,
  isFetching,
  error,
  industryData = {},
}: ComparisonGraphs): React.ReactElement => {
  const isYesterdayPositive = crData.yesterday >= industryData.yesterday;
  const isLwsdPositive = crData.lwsd >= industryData.lwsd;

  return (
    <Card>
      <CardHeader>
        <p>Overall Conversion rate</p>
        <p>
          The percentage of users who successfully complete a payment after initiating Razorpay
          Checkout as of today, yesterday and last-week-same-day
        </p>
      </CardHeader>
      {isFetching || error ? (
        <LoadingError isLoading={isFetching} error={error} showDescription={false} />
      ) : (
        <>
          <CardCenter>{crData.today} %</CardCenter>
          <CardFooter>
            <div>
              {isYesterdayPositive ? <UpIcon /> : <DownIcon />}
              <b className={isYesterdayPositive ? 'green' : 'red'}>{crData.yesterday} % </b>
              Yesterday
            </div>
            <div>
              {isLwsdPositive ? <UpIcon /> : <DownIcon />}
              <b className={isLwsdPositive ? 'green' : 'red'}> {crData.lwsd} % </b>Last{' '}
              {WEEKS_MAP[dow - 1]}
            </div>
          </CardFooter>
        </>
      )}
    </Card>
  );
};

export default CRComparison;
