import React from 'react';
import { Card, CardCenter, CardFooter, CardHeader } from './styled';
import moment from 'moment';
import { WEEKS_MAP } from 'merchant/views/PaymentMetrics/constants';

import LoadingError from './LoadingError';
import { ComparisonGraphs } from 'merchant/views/PaymentMetrics/types';

const dow = moment().day();

const IndustryCRComparison = ({
  crData,
  isFetching,
  error,
}: ComparisonGraphs): React.ReactElement => {
  return (
    <Card>
      <CardHeader>
        <p>Category Level Conversion Rate</p>
        <p>
          The percentage of users who successfully complete a payment after initiating Razorpay
          Checkout within your category as of today, yesterday and last-week-same-day
        </p>
      </CardHeader>
      {isFetching || error ? (
        <LoadingError isLoading={isFetching} error={error} showDescription={false} />
      ) : (
        <>
          <CardCenter>{crData.today} %</CardCenter>
          <CardFooter>
            <div>
              <b>{crData.yesterday} % </b>
              Yesterday
            </div>
            <div>
              <b> {crData.lwsd} % </b>Last {WEEKS_MAP[dow - 1]}
            </div>
          </CardFooter>
        </>
      )}
    </Card>
  );
};

export default IndustryCRComparison;
