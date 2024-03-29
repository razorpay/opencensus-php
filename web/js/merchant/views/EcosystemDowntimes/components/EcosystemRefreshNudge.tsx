import React, { useContext, useEffect, useState } from 'react';
import { EcosystemDowntimeContext } from 'merchant/views/EcosystemDowntimes/context';
import { EcosystemRefreshNudgeContainer } from 'merchant/views/EcosystemDowntimes/styles';
import { RefreshIcon, Spinner, Text } from '@razorpay/blade/components';
import GenericTooltip from 'common/ui/Tooltip';
import { getRemainingTime } from 'merchant/views/EcosystemDowntimes/helpers';

const INTERVAL = 1000;

const EcosystemRefreshNudge = ({ waitInterval }: { waitInterval: number }): JSX.Element | null => {
  const { state, refreshData } = useContext(EcosystemDowntimeContext);
  const {
    lastUpdatedAt,
    isOngoingDowntimeFetching: isFetching,
    isOngoingDowntimesError,
    isPreviousDowntimesError,
  } = state;
  const [secondsLeft, setSecondsLeft] = useState<number>(waitInterval);
  const [isRefreshEnabled, setIsRefreshEnabled] = useState(false);

  useEffect(() => {
    let timer: ReturnType<typeof setTimeout>;
    if (!isFetching && !isOngoingDowntimesError && !isPreviousDowntimesError) {
      setIsRefreshEnabled(false);
      setSecondsLeft(waitInterval);
      timer = setTimeout(() => {
        setIsRefreshEnabled(true);
      }, waitInterval * 1000);
    }
    return () => {
      clearTimeout(timer);
    };
  }, [waitInterval, isFetching, isOngoingDowntimesError, isPreviousDowntimesError]);

  useEffect(() => {
    const timer: ReturnType<typeof setInterval> = setInterval(() => {
      const next = secondsLeft - 1;
      if (next < 0) {
        clearInterval(timer);
      } else {
        setSecondsLeft(secondsLeft - 1);
      }
    }, INTERVAL);
    return () => {
      clearInterval(timer);
    };
  }, [secondsLeft]);

  useEffect(() => {
    if (!!isOngoingDowntimesError || !!isPreviousDowntimesError) {
      setIsRefreshEnabled(true);
    }
  }, [isOngoingDowntimesError, isPreviousDowntimesError]);

  const handleRefresh = () => {
    if (isRefreshEnabled) refreshData();
  };

  if (!lastUpdatedAt && !isOngoingDowntimesError) return null;

  return (
    <EcosystemRefreshNudgeContainer
      isRefreshEnabled={isRefreshEnabled}
      aria-label="ecosystem-refresh-nudge"
    >
      <Text color="surface.text.gray.muted">Last updated at : {lastUpdatedAt}</Text>
      <div
        aria-label="ecosystem-refresh-button"
        className={`refresh-btn ${isRefreshEnabled ? 'enabled-refresh' : 'disabled-refresh'}`}
        onClick={handleRefresh}
      >
        {isFetching ? (
          <Spinner size="large" accessibilityLabel="ecosystem-downtimes-refresh-spinner" />
        ) : (
          <>
            <RefreshIcon
              color={
                isRefreshEnabled
                  ? 'interactive.icon.primary.normal'
                  : 'interactive.icon.primary.disabled'
              }
              size="medium"
            />
            <Text>Refresh</Text>
            {isRefreshEnabled ? null : (
              <GenericTooltip align="bottom">
                Please try to refresh after{' '}
                <span data-testid="time-left">{getRemainingTime(secondsLeft)}</span>
              </GenericTooltip>
            )}
          </>
        )}
      </div>
    </EcosystemRefreshNudgeContainer>
  );
};

export default EcosystemRefreshNudge;
