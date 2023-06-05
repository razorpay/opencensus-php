import React, { useEffect, useRef } from 'react';
import { connect } from 'react-redux';
import {
  fetchSchedulesSuccess,
  handleSchedulesPageTrack,
  startSchedulePoll,
  stopSchedulePoll,
} from 'merchant_common/views/Reports/redux/reducer';
import EmptyDashboardIllustration from 'assets/reports/empty-dashboard.svg';
import { SchedulesTablePropsType } from 'merchant_common/views/Reports/features/Schedules/types';
import { Table, EmptyTable, Box } from 'merchant_common/views/Reports/components';
import { baseSchedulesTableTemplate } from 'merchant_common/views/Reports/features/Schedules/data/baseTableTemplate';
import { emptySchedulesTableTemplate } from 'merchant_common/views/Reports/features/Schedules/data/emptyTableTemplate';
import { useDashboardType } from 'merchant_common/views/Reports/contexts/ReportsContext';
import { getReportsDashboardConfig } from 'merchant_common/views/Reports/configs/refDashboard.config';
import { showNotification } from 'merchant_common/reducers/notifications';
import { openModal } from 'merchant_common/reducers/modals';
import { initiateSchedulesPoll } from 'merchant_common/views/Reports/api/schedules';
import { trackScheduleSection } from 'merchant_common/views/Reports/configs/analytics.config';

const mapStateToProps = ({ reportsCore }, { dashboardType }) => {
  const { loading, allSchedules, pageTrack, filter, genericPoll, totalCount } =
    reportsCore[dashboardType].schedules;

  return {
    isSchedulesLoaded: !loading,
    allSchedules,
    pageTrack,
    filter,
    genericPoll,
    totalCount,
  };
};

export const mapDispatchToProps = (dispatch, { dashboardType }) => {
  return {
    startSchedulePoll: (payload) => dispatch(startSchedulePoll({ ...payload, dashboardType })),
    fetchSchedulesSuccess: (payload) =>
      dispatch(fetchSchedulesSuccess({ ...payload, dashboardType })),
    handleSchedulesPageTrack: (payload) =>
      dispatch(handleSchedulesPageTrack({ pageNo: payload, dashboardType })),
    stopSchedulePoll: (payload) => dispatch(stopSchedulePoll({ ...payload, dashboardType })),
    showNotification: (payload) => dispatch(showNotification(payload)),
    openModal: (modal) => dispatch(openModal(modal)),
  };
};

const SchedulesTableComponent = connect(
  mapStateToProps,
  mapDispatchToProps,
)(
  ({
    isSchedulesLoaded,
    allSchedules,
    fixedHeaders,
    fetchSchedulesSuccess,
    handleSchedulesPageTrack,
    pageTrack,
    filter,
    genericPoll,
    stopSchedulePoll,
    totalCount,
    dashboardType,
    showNotification,
    startSchedulePoll,
    openModal,
  }: SchedulesTablePropsType): JSX.Element => {
    // will hold abort fn of presently ongoing poll
    const abortPresentlyActivePoll = useRef<any>();
    const { headers } = getReportsDashboardConfig(dashboardType);

    useEffect(() => {
      // abort present poll
      if (typeof abortPresentlyActivePoll.current === 'function') {
        abortPresentlyActivePoll.current();
      }

      if (genericPoll) {
        // start new poll with new page/filter
        const { abort } = initiateSchedulesPoll({
          queryParams: {
            filter,
            page: pageTrack,
          },
          headers,
          pollResSuccessCallback: (data) => {
            if (data) {
              const { total_count, items } = data;
              if (total_count)
                fetchSchedulesSuccess({
                  totalCount: total_count,
                  allSchedules: items,
                });
            }
          },
          pollResFailedCallback: () => {
            showNotification({
              type: 'error',
              message: 'Unable to fetch schedules log, please try again later.',
            });
            trackScheduleSection({
              actionName: 'Schedules Poll Failed',
              dashboardType,
            });
            if (typeof abortPresentlyActivePoll.current === 'function') {
              abortPresentlyActivePoll.current();
            }
          },
          // updates redux state when generic poll times out
          onPollStopCallback: () => stopSchedulePoll(),
        });

        // update ref with new poll's abort fn
        abortPresentlyActivePoll.current = abort;
      }
    }, [pageTrack, filter, genericPoll]);

    const onViewActivityOpen = () => {};

    useEffect(() => {
      startSchedulePoll();
      // abort poll when component unmounts on tab change
      return () => {
        if (typeof abortPresentlyActivePoll.current === 'function') {
          abortPresentlyActivePoll.current();
        }
        // updates redux state when poll stops
        startSchedulePoll();
      };
    }, []);

    return (
      <Box padding={['spacing.0', 'spacing.4', 'spacing.0', 'spacing.4']}>
        <Table
          fixedHeaders={fixedHeaders}
          template={baseSchedulesTableTemplate}
          rows={allSchedules}
          currentPage={pageTrack}
          onPageChange={handleSchedulesPageTrack}
          centeredHeaders={[1, 3, 4, 5]}
          loading={!isSchedulesLoaded}
          onLoadingSkeletonTemplate={emptySchedulesTableTemplate}
          totalRows={totalCount}
          additionalInfo={{ onViewActivityOpen, openModal }}
          renderOnEmpty={() => (
            <EmptyTable
              title="No Schedules Found :("
              desc="Create report schedule to receive reports automatically to your email."
              src={EmptyDashboardIllustration}
            />
          )}
        />
      </Box>
    );
  },
);

export const SchedulesTable = (props) => {
  const dashboardType = useDashboardType();
  return <SchedulesTableComponent dashboardType={dashboardType} {...props} />;
};
