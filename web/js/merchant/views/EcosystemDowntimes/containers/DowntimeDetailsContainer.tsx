import React, { useContext } from 'react';
import { EcosystemDowntimeContext } from 'merchant/views/EcosystemDowntimes/context';
import DowntimeDetailsHeader from 'merchant/views/EcosystemDowntimes/components/DowntimeDetailsHeader';
import DowntimeHistory from 'merchant/views/EcosystemDowntimes/components/DowntimeHistory';
import EcosystemHealthError from 'merchant/views/EcosystemDowntimes/components/EcosystemHealthError';
import { Text, Badge, Spinner } from '@razorpay/blade/components';
import DowntimeTiles from 'merchant/views/EcosystemDowntimes/components/DowntimeTiles';
import DowntimeDetailsCurrentStatus from 'merchant/views/EcosystemDowntimes/components/DowntimeDetailsCurrentStatus';
import { isMobileResolution } from 'common/utils/rzp-utils';
import { DOWNTIME_SUMMARY_FIELDS, STATUS } from 'merchant/views/EcosystemDowntimes/constants';
import type { DowntimeMetaDataType } from 'merchant/views/EcosystemDowntimes/types';
import { DowntimeHistoryContent } from 'merchant/views/EcosystemDowntimes/styles';
import SuccessRateSummary from 'merchant/views/EcosystemDowntimes/components/SuccessRateSummary';

type processPreviousDowntimeDataReturnType = {
  icon?: JSX.Element;
  content: JSX.Element;
};

export const processPreviousDowntimeData = (
  data: DowntimeMetaDataType[],
): processPreviousDowntimeDataReturnType[] =>
  data.map((previousDowntimeObj) => {
    const { fromToString, duration, severity } = previousDowntimeObj;
    return {
      icon: STATUS[severity].icon,
      content: (
        <DowntimeHistoryContent>
          <div className="downtime-info">
            <Text size="small">
              {fromToString} {duration ? `(${duration})` : ''}
            </Text>
          </div>
          <div className="badge-container">
            <Badge
              emphasis="subtle"
              size="large"
              color={severity === STATUS.high.slug ? 'negative' : 'notice'}
            >
              {STATUS[severity].text}
            </Badge>
          </div>
        </DowntimeHistoryContent>
      ),
    };
  });

const DowntimeDetailsContainer = (): JSX.Element | null => {
  const isMobile = isMobileResolution();
  const { state } = useContext(EcosystemDowntimeContext);
  const {
    focusedInstrument,
    isPreviousDowntimesFetching,
    isPreviousDowntimesLoading,
    isPreviousDowntimesError,
    previousDowntimes,
    activeDowntimes,
  } = state;

  if (!focusedInstrument) return null;

  const isLoading = isPreviousDowntimesLoading || isPreviousDowntimesFetching;

  if (isLoading) {
    return (
      <div className="ecosystem-downtime-details" aria-label="ecosystem-downtime-details">
        <Spinner size="large" accessibilityLabel="ecosystem-downtimes-refresh-spinner" />
      </div>
    );
  }

  if (!!isPreviousDowntimesError) return <EcosystemHealthError />;

  const { key, method, group, srKey } = focusedInstrument;
  const activeDowntimeForInstrument = activeDowntimes?.[method]?.[group]?.[key];
  const pastDowntimesForInstrument =
    previousDowntimes?.[method]?.[group]?.[key]?.previousDowntimes || [];

  const processedDowntimesForTimeline = processPreviousDowntimeData(pastDowntimesForInstrument);

  return (
    <div className="ecosystem-downtime-details" aria-label="ecosystem-downtime-details">
      <DowntimeDetailsHeader instrument={focusedInstrument} isMobile={isMobile} />
      <DowntimeTiles
        isMobile={isMobile}
        activeDowntime={activeDowntimeForInstrument}
        pastDowntimes={pastDowntimesForInstrument}
        summaryFields={DOWNTIME_SUMMARY_FIELDS}
      />
      {!!srKey ? (
        <SuccessRateSummary isMobile={isMobile} srKey={srKey} instrument={key} method={method} />
      ) : null}
      <DowntimeDetailsCurrentStatus
        instrument={focusedInstrument}
        isMobile={isMobile}
        activeDowntime={activeDowntimeForInstrument}
      />
      <DowntimeHistory isMobile={isMobile} pastDowntimes={processedDowntimesForTimeline} />
    </div>
  );
};

export default DowntimeDetailsContainer;
