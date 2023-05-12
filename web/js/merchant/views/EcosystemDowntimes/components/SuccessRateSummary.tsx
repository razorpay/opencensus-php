import React from 'react';
import { compose } from 'redux';
import { connect } from 'react-redux';
import { useQuery } from 'react-query';
import { fetchSuccessRate } from 'merchant/views/EcosystemDowntimes/services';
import DowntimeSummaryTile from './DowntimeSummaryTile';
import { DowntimeTilesContainer } from 'merchant/views/EcosystemDowntimes/styles';
import Shimmer from 'common/components/Shimmer';
import { Alert, Theme } from '@razorpay/blade/components';
import { SR_QUERY_CACHE_KEY } from 'merchant/views/EcosystemDowntimes/constants';
import styled from 'styled-components';
import { showNotification } from 'merchant_common/reducers/notifications';
import { getSrDatPointsFromResponse } from 'merchant/views/EcosystemDowntimes/helpers';

type SuccessRateSummaryTypes = {
  isMobile: boolean;
  srKey: string;
  instrument: string;
  method: string;
  showNotification: (payload) => void;
};

const Loader = () => (
  <DowntimeTilesContainer aria-label="sr-summary-loader">
    <Shimmer height="11vh" width="100%" variant="rounded" />
    <Shimmer height="11vh" width="100%" variant="rounded" />
  </DowntimeTilesContainer>
);

const AlertWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  margin-bottom: ${theme.spacing[5]}px;
`,
);

const SUMMARIES = [
  {
    name: 'successfulPayments',
    description: 'Successful Payments',
    subText: '(Past 1 week)',
    valueFn: ({ successful }) => successful.toLocaleString('en-IN'),
  },
  {
    name: 'unsuccessfulPayments',
    description: 'Unsuccessful Payments',
    subText: '(Past 1 week)',
    valueFn: ({ unsuccessful }) => unsuccessful.toLocaleString('en-IN'),
  },
];

const SuccessRateSummary = ({
  isMobile,
  srKey,
  instrument,
  method,
  showNotification,
}: SuccessRateSummaryTypes) => {
  const handleError = () => {
    showNotification({
      type: 'error',
      message: 'Something went wrong while fetching success rate',
    });
  };

  const {
    data: srResponse,
    isLoading,
    error: isFetchFailed,
  } = useQuery([SR_QUERY_CACHE_KEY, srKey], () => fetchSuccessRate({ srKey }), {
    retry: 2,
    retryDelay: 800,
    staleTime: Infinity,
    onError: handleError,
    onSuccess: (srResponse) => {
      if (srResponse) {
        const { data } = srResponse;
        if (data.Code) handleError();
      }
    },
  });

  if (isLoading) return <Loader />;

  const srData = getSrDatPointsFromResponse({
    srResponse,
    method,
    instrument,
  });
  const { total, successful, unsuccessful, isError, methodName, instrumentName } = srData;

  if (isError || isFetchFailed) {
    return (
      <AlertWrapper aria-label="sr-summary-error">
        <Alert
          description="Something went wrong while fetching successful payments"
          intent="negative"
        />
      </AlertWrapper>
    );
  }

  if (total === 0) {
    return (
      <AlertWrapper aria-label="no-payments-sr">
        <Alert
          description={`No payments were attempted via ${instrumentName} (${methodName}) in the past one week`}
          intent="information"
          isDismissible={false}
        />
      </AlertWrapper>
    );
  }

  return (
    <DowntimeTilesContainer aria-label="sr-summary-container">
      {SUMMARIES.map(({ name, description, subText, valueFn }) => (
        <DowntimeSummaryTile
          key={name}
          description={description}
          subText={subText}
          value={valueFn({ successful, unsuccessful })}
          isMobile={isMobile}
        />
      ))}
    </DowntimeTilesContainer>
  );
};

export default compose(
  connect(null, {
    showNotification,
  }),
)(SuccessRateSummary);
