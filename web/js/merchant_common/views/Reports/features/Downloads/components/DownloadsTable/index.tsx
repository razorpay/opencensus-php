import React, { useEffect, useRef } from 'react';
import { connect } from 'react-redux';
import { initiateLogsPoll } from 'merchant_common/views/Reports/api/downloads';
import {
  fetchLogsSuccess,
  handleDownloadsPageTrack,
  startLogsPoll,
  stopLogsPoll,
} from 'merchant_common/views/Reports/redux/reducer';
import EmptyDashboardIllustration from 'assets/reports/empty-dashboard.svg';
import { DownloadsTablePropsType } from 'merchant_common/views/Reports/features/Downloads/types';
import { Table, EmptyTable, Box } from 'merchant_common/views/Reports/components';
import { baseDownloadsTableTemplate } from 'merchant_common/views/Reports/features/Downloads/configs/baseTableTemplate';
import { emptyDownloadsTableTemplate } from 'merchant_common/views/Reports/features/Downloads/configs/emptyTableTemplate';
import { useDashboardType } from 'merchant_common/views/Reports/contexts/ReportsContext';
import { getReportsDashboardConfig } from 'merchant_common/views/Reports/configs/refDashboard.config';
import { showNotification } from 'merchant_common/reducers/notifications';
import { trackDownloadsSection } from 'merchant_common/views/Reports/configs/analytics.config';
import { useReportsSplitzExperiments } from 'merchant_common/views/Reports/hooks';

const mapStateToProps = ({ reportsCore }, { dashboardType }) => {
  const { loading, logs, pageTrack, filter, genericPoll, totalCount } =
    reportsCore[dashboardType].downloads;

  return {
    isLogsLoaded: !loading,
    logs,
    pageTrack,
    filter,
    genericPoll,
    totalCount,
  };
};

export const mapDispatchToProps = (dispatch, { dashboardType }) => {
  return {
    startLogsPoll: (payload) => {
      dispatch(startLogsPoll({ ...payload, dashboardType }));
      trackDownloadsSection({
        actionName: 'Downloads Logs Poll Start',
        dashboardType,
      });
    },
    stopLogsPoll: (payload) => {
      dispatch(stopLogsPoll({ ...payload, dashboardType }));
      trackDownloadsSection({
        actionName: 'Downloads Logs Poll Stop',
        dashboardType,
      });
    },
    fetchLogsSuccess: (payload) => dispatch(fetchLogsSuccess({ ...payload, dashboardType })),
    handlePageChange: (payload) =>
      dispatch(handleDownloadsPageTrack({ pageNo: payload, dashboardType })),
    showNotification: (payload) => dispatch(showNotification(payload)),
  };
};

const DownloadsTableComponent = connect(
  mapStateToProps,
  mapDispatchToProps,
)(
  ({
    isLogsLoaded,
    logs,
    fixedHeaders,
    fetchLogsSuccess,
    handlePageChange,
    pageTrack,
    filter,
    genericPoll,
    stopLogsPoll,
    totalCount,
    dashboardType,
    showNotification,
    startLogsPoll,
    headers,
  }: DownloadsTablePropsType): JSX.Element => {
    // will hold abort fn of presently ongoing poll
    const abortPresentlyActivePoll = useRef<any>();
    const { headers: defaultHeaders } = getReportsDashboardConfig(dashboardType);
    const { isEdgeEnabled } = useReportsSplitzExperiments();

    useEffect(() => {
      // abort present poll.
      if (typeof abortPresentlyActivePoll.current === 'function') {
        abortPresentlyActivePoll.current();
      }

      if (genericPoll) {
        // start new poll with new page/filter
        const { abort } = initiateLogsPoll({
          queryParams: {
            filter,
            page: pageTrack,
          },
          headers: headers ?? defaultHeaders,
          pollResSuccessCallback: (data) => {
            if (data) {
              const { total_count, items } = data;
              fetchLogsSuccess({
                totalCount: total_count!,
                logs: items,
              });
            }
          },
          pollResFailedCallback: () => {
            showNotification({
              type: 'error',
              message: 'Unable to fetch download logs, please try again later.',
            });
            trackDownloadsSection({
              actionName: 'Downloads Logs Poll Failed',
              dashboardType,
            });

            if (typeof abortPresentlyActivePoll.current === 'function') {
              abortPresentlyActivePoll.current();
            }
          },
          // updates redux state when generic poll times out
          onPollStopCallback: () => stopLogsPoll(),
          isEdgeEnabled,
        });

        // update ref with new poll's abort fn
        abortPresentlyActivePoll.current = abort;
      }
    }, [pageTrack, filter, genericPoll]);

    useEffect(() => {
      startLogsPoll();
      // abort poll when component unmounts on tab change
      return () => {
        if (typeof abortPresentlyActivePoll.current === 'function') {
          abortPresentlyActivePoll.current();
        }
        // updates redux state when poll stops
        stopLogsPoll();
      };
    }, []);

    return (
      <Box padding={['spacing.0', 'spacing.4', 'spacing.0', 'spacing.4']}>
        <Table
          fixedHeaders={fixedHeaders}
          template={baseDownloadsTableTemplate}
          rows={Object.values(logs)}
          currentPage={pageTrack}
          onPageChange={handlePageChange}
          centeredHeaders={[2, 4, 5]}
          loading={!isLogsLoaded}
          onLoadingSkeletonTemplate={emptyDownloadsTableTemplate}
          totalRows={totalCount}
          additionalInfo={{
            trackDownloadFile: trackDownloadsSection,
          }}
          renderOnEmpty={() => (
            <EmptyTable
              title="No Reports Found :("
              desc="Download reports for your business just in one click."
              src={EmptyDashboardIllustration}
            />
          )}
        />
      </Box>
    );
  },
);

export const DownloadsTable = (props) => {
  const dashboardType = useDashboardType();
  return <DownloadsTableComponent dashboardType={dashboardType} {...props} />;
};
