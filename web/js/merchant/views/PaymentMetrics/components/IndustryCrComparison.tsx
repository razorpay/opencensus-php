import React, { useEffect, useState } from 'react';
import { TrendingUpIcon, TrendingDownIcon } from '@razorpay/blade/components';
import { Card, CardCenter, CardFooter, CardHeader } from './styled';
import { getIndustryOverallCRData } from 'merchant/views/PaymentMetrics/helpers';
import moment from 'moment';
import { WEEKS_MAP } from 'merchant/views/PaymentMetrics/constants';

import LoadingError from './LoadingError';

const UpIcon = () => {
  return <TrendingUpIcon color="feedback.icon.positive.lowContrast" size="medium" />;
};
const DownIcon = () => {
  return <TrendingDownIcon color="feedback.icon.negative.lowContrast" size="medium" />;
};

const lte = moment().endOf('day').unix(); // today
const gte = moment().subtract(7, 'days').startOf('day').unix(); // lwsd
const dow = moment().day();

const getTimeStampValue = (
  data: Array<{ timestamp: number; value: number }>,
  timestamp: number,
) => {
  const value = data.filter((item) => item.timestamp === timestamp)?.[0]?.value || 0;
  return Math.round(value);
};

const IndustryCRComparison = ({ category = '' }): React.ReactElement => {
  const [isFetching, setFetching] = useState(false);
  const [error, setError] = useState('');
  const [crData, setCrData] = useState({ yesterday: 0, today: 0, lwsd: 0 });

  useEffect(() => {
    setFetching(true);
    getIndustryOverallCRData({ lte, gte, category })
      .then((resp) => {
        if (resp.data?.ERROR || resp.errors) {
          setError('Try Again Later!');
        } else {
          const yesterday = moment().subtract(1, 'days').startOf('day').unix();
          const today = moment().startOf('day').unix();
          const data =
            resp.data?.checkout_industry_level_cr?.result ||
            ([] as Array<{ timestamp: number; value: number }>);
          setCrData({
            yesterday: getTimeStampValue(data, yesterday),
            today: getTimeStampValue(data, today),
            lwsd: getTimeStampValue(data, gte),
          });
        }
      })
      .catch(
        /* istanbul ignore next */
        () => {
          setError('Try Again Later!');
        },
      )
      .finally(() => {
        setFetching(false);
      });
  }, []);

  const isYesterdayPositive = crData.yesterday >= crData.today;
  const isLwsdPositive = crData.lwsd >= crData.today;

  return (
    <Card>
      <CardHeader>
        <p>Industry Level Conversion Rate</p>
        <p>
          The percentage of payments submitted out of all attempted payments within your specific
          industry as of yesterday and last-week-same-day
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

export default IndustryCRComparison;
