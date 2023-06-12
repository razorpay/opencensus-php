import React, { useEffect, useRef } from 'react';
import { connect } from 'react-redux';
import EmptyDashboardIllustration from 'assets/reports/empty-dashboard.svg';
import { Table, EmptyTable } from 'merchant_common/views/Reports/components';
import { useDashboardType } from 'merchant_common/views/Reports/contexts/ReportsContext';
import { getReportsDashboardConfig } from 'merchant_common/views/Reports/configs/refDashboard.config';
import { showNotification } from 'merchant_common/reducers/notifications';
import { initiateScheduleRunHistoryPoll } from 'merchant_common/views/Reports/api/runHistory';
import { RunHistoryTablePropsType } from 'merchant_common/views/Reports/components/ReportModal/components/ScheduleRunHistory/types';
import { baseRunHistoryTableTemplate } from 'merchant_common/views/Reports/components/ReportModal/components/ScheduleRunHistory/data/baseTableTemplate';
import { emptyRunHistoryTableTemplate } from 'merchant_common/views/Reports/components/ReportModal/components/ScheduleRunHistory/data/emptyTableTemplate';
import { trackSchedulesRunHistoryModal } from 'merchant_common/views/Reports/configs/analytics.config';

export const mapDispatchToProps = (dispatch) => {
  return {
    showNotification: (payload) => dispatch(showNotification(payload)),
  };
};

const RunHistoryTableComponent = connect(
  null,
  mapDispatchToProps,
)(
  ({
    dashboardType,
    showNotification,
    scheduleId,
    logsHistoryReducer: {
      isPollActive,
      isLoading,
      filter,
      pageTrack,
      logs,
      totalLogsCount,
      handlePageTrack,
      handleLogsFetchSuccess,
      setIsPollActive,
    },
  }: RunHistoryTablePropsType): JSX.Element => {
    // will hold abort fn of presently ongoing poll
    const abortPresentlyActivePoll = useRef<any>();
    const { headers } = getReportsDashboardConfig(dashboardType);

    useEffect(() => {
      // abort present poll
      if (typeof abortPresentlyActivePoll.current === 'function') {
        abortPresentlyActivePoll.current();
      }
      if (isPollActive) {
        // start new poll with new page/filter
        const { abort } = initiateScheduleRunHistoryPoll({
          queryParams: {
            filter: filter.value,
            page: pageTrack,
            scheduleId,
          },
          headers,
          pollResSuccessCallback: (data) => {
            if (data) {
              handleLogsFetchSuccess(data);
            }
          },
          pollResFailedCallback: () => {
            showNotification({
              type: 'error',
              message: 'Unable to fetch download logs, please try again later.',
            });
            trackSchedulesRunHistoryModal({
              actionName: 'Logs Poll Failed',
              dashboardType,
            });
            if (typeof abortPresentlyActivePoll.current === 'function') {
              abortPresentlyActivePoll.current();
            }
          },
          // updates redux state when generic poll times out
          onPollStopCallback: () => setIsPollActive(false),
        });

        // update ref with new poll's abort fn
        abortPresentlyActivePoll.current = abort;
      }
    }, [pageTrack, filter.value, isPollActive]);

    useEffect(() => {
      setIsPollActive(true);
      // abort poll when component unmounts on tab change
      return () => {
        if (typeof abortPresentlyActivePoll.current === 'function') {
          abortPresentlyActivePoll.current();
        }
        // updates redux state when poll stops
        setIsPollActive(false);
      };
    }, []);

    return (
      <Table
        fixedHeaders={false}
        template={baseRunHistoryTableTemplate}
        rows={logs}
        currentPage={pageTrack}
        onPageChange={handlePageTrack}
        centeredHeaders={[2, 4, 5]}
        loading={isLoading}
        onLoadingSkeletonTemplate={emptyRunHistoryTableTemplate}
        totalRows={totalLogsCount}
        pageSize={10}
        additionalInfo={{
          trackDownloadFile: trackSchedulesRunHistoryModal,
        }}
        renderOnEmpty={() => (
          <EmptyTable
            title="No Reports Found :("
            desc="You don't have any reports delivered yet."
            src={EmptyDashboardIllustration}
          />
        )}
      />
    );
  },
);

export const RunHistoryTable = (props) => {
  const dashboardType = useDashboardType();
  return <RunHistoryTableComponent dashboardType={dashboardType} {...props} />;
};
