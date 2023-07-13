import React, { useEffect, useState } from 'react';
import { TrendingUpIcon, TrendingDownIcon } from '@razorpay/blade/components';
import GenericTooltip from 'common/ui/Tooltip';
import { Card, CardCenter, CardFooter, CardHeader } from './styled';
import { getOverallCRData } from 'merchant/views/PaymentMetrics/helpers';
import moment from 'moment';

import LoadingError from './LoadingError';

const UpIcon = () => {
  return <TrendingUpIcon color="feedback.icon.positive.lowContrast" size="medium" />;
};
const DownIcon = () => {
  return <TrendingDownIcon color="feedback.icon.negative.lowContrast" size="medium" />;
};

const CRComparison = (): React.ReactElement => {
  const [isFetching, setFetching] = useState(false);
  const [error, setError] = useState('');
  const [crData, setCrData] = useState({ yesterday: 0, today: 0, lwsd: 0 });

  const getTimeStampValue = (
    data: Array<{ timestamp: number; value: number }>,
    timestamp: number,
  ) => {
    const value = data.filter((item) => item.timestamp === timestamp)?.[0]?.value || 0;
    return Math.round(value);
  };

  useEffect(() => {
    setFetching(true);
    const lte = moment().endOf('day').unix(); // today
    const gte = moment().subtract(7, 'days').startOf('day').unix(); // lwsd

    getOverallCRData({ lte, gte })
      .then((resp) => {
        if (resp.data?.ERROR || resp.errors) {
          setError(resp.data?.ERROR || resp.errors?.[0]);
        } else {
          const yesterday = moment().subtract(1, 'days').startOf('day').unix();
          const today = moment().startOf('day').unix();
          const data =
            resp.data?.checkout_overall_cr?.result ||
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
        <p>Overall Conversion rate</p>
        <p>Today's Total CR</p>
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
              <b className={isLwsdPositive ? 'green' : 'red'}> {crData.lwsd} % </b>LWSD
              <GenericTooltip align="top">Last Week Same day</GenericTooltip>
            </div>
          </CardFooter>
        </>
      )}
    </Card>
  );
};

export default CRComparison;
