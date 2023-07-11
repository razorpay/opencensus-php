import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { openModal } from 'merchant_common/reducers/modals';
import { useDashboardType } from 'merchant_common/views/Reports/contexts/ReportsContext';
import { SchedulesTableIconControlContainer } from 'merchant_common/views/Reports/features/Schedules/styled';
import {
  PauseIcon,
  TrashIcon,
  PlayIcon,
  IconButton,
  ReportModal,
  EditIcon,
  Spinner,
  MinusIcon,
} from 'merchant_common/views/Reports/components';
import {
  deleteSchedule,
  pauseSchedule,
  resumeSchedule,
} from 'merchant_common/views/Reports/api/schedules';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  SchedulesActionType,
  trackScheduleSection,
} from 'merchant_common/views/Reports/configs/analytics.config';
import { ControlActionsArgs, ControlActionsType, HandleControlActionFnParam } from './types';
import { startSchedulePoll, stopSchedulePoll } from 'merchant_common/views/Reports/redux/reducer';
import { Button } from 'merchant_common/views/Reports/components/styled';

const mapDispatchToProps = (dispatch, { dashboardType }) => ({
  openModal: (modal) => dispatch(openModal(modal)),
  startSchedulePoll: (payload) => dispatch(startSchedulePoll({ ...payload, dashboardType })),
  stopSchedulePoll: (payload) => dispatch(stopSchedulePoll({ ...payload, dashboardType })),
  showNotification: (payload) => dispatch(showNotification(payload)),
});

const ControlActionsComponent = connect(
  null,
  mapDispatchToProps,
)(
  ({
    openModal,
    scheduleData,
    showNotification,
    startSchedulePoll,
    stopSchedulePoll,
    dashboardType,
  }: ControlActionsArgs) => {
    const { status, id, name } = scheduleData;
    const [isActionsLoading, setIsActionsLoading] = useState<ControlActionsType[]>([]);

    const handleControlAction = ({ action, icon, promise }: HandleControlActionFnParam) => {
      const eventType = action === 'delete' ? 'Delete' : action === 'pause' ? 'Pause' : 'Resume';
      openModal({
        component: (
          <ReportModal
            type="confirm_modal"
            dashboardType={dashboardType}
            params={{
              modalConfig: {
                title: 'Please Confirm!',
                desc: `Are you sure you want to ${action} this schedule?`,
                confirmBtn: {
                  onClick: ({ setLoading, closeModal }) => {
                    setLoading(true);
                    if (!id) return;

                    stopSchedulePoll();

                    promise({
                      headers: {},
                      scheduleId: id,
                    })
                      .then(() => {
                        trackScheduleSection({
                          actionName: `${eventType} Successful` as keyof typeof SchedulesActionType,
                          dashboardType,
                        });
                        showNotification({
                          type: 'success',
                          message: `Schedule ${action[0].toUpperCase() + action.slice(1)}d!`,
                        });
                        setIsActionsLoading([...isActionsLoading, action]);
                        closeModal();
                      })
                      .catch(() => {
                        trackScheduleSection({
                          actionName: `${eventType} Failed` as keyof typeof SchedulesActionType,
                          dashboardType,
                        });
                        showNotification({
                          type: 'error',
                          message: `Unable to ${action} this schedule. Please try again later.`,
                        });
                      })
                      .finally(() => {
                        startSchedulePoll();
                      });
                  },
                  icon,
                  label: `${action[0].toUpperCase() + action.slice(1)} Schedule`,
                },
                alert: {
                  intent:
                    action === 'delete'
                      ? 'negative'
                      : action === 'pause'
                      ? 'information'
                      : 'notice',
                  description: `You are about to ${action} this schedule (${name}).`,
                },
              },
            }}
          />
        ),
        size: 'custom',
      });
    };

    const handleDeleteSchedule = () => {
      trackScheduleSection({
        actionName: 'Delete Triggered',
        dashboardType,
      });
      handleControlAction({
        action: 'delete',
        icon: TrashIcon,
        promise: deleteSchedule,
      });
    };

    const handlePauseSchedule = () => {
      trackScheduleSection({
        actionName: 'Pause Triggered',
        dashboardType,
      });
      handleControlAction({
        action: 'pause',
        icon: PauseIcon,
        promise: pauseSchedule,
      });
    };

    const handleResumeSchedule = () => {
      trackScheduleSection({
        actionName: 'Resume Triggered',
        dashboardType,
      });
      handleControlAction({
        action: 'resume',
        icon: PlayIcon,
        promise: resumeSchedule,
      });
    };

    const handleEditSchedule = () => {
      trackScheduleSection({
        actionName: 'Edit Modal Open Triggered',
        dashboardType,
      });
      openModal({
        component: (
          <ReportModal
            type="create_edit_schedule"
            dashboardType={dashboardType}
            params={{
              scheduleData,
            }}
          />
        ),
        size: 'custom',
      });
    };

    useEffect(() => {
      if (isActionsLoading.length) setIsActionsLoading([]);
    }, [status]);

    return (
      <SchedulesTableIconControlContainer>
        <IconButton
          icon={EditIcon}
          size="medium"
          onClick={handleEditSchedule}
          accessibilityLabel="Edit Schedule"
        />

        {status === 'finished' ? (
          <MinusIcon size="medium" color="action.icon.secondary.disabled" />
        ) : isActionsLoading.includes('pause') || isActionsLoading.includes('resume') ? (
          <Spinner size="medium" accessibilityLabel="Status Change In Process" />
        ) : status === 'active' ? (
          <IconButton
            icon={PauseIcon}
            size="medium"
            onClick={handlePauseSchedule}
            accessibilityLabel="Pause Schedule"
          />
        ) : (
          <IconButton
            icon={PlayIcon}
            size="medium"
            onClick={handleResumeSchedule}
            accessibilityLabel="Resume Schedule"
          />
        )}

        {isActionsLoading.includes('delete') ? (
          <Button>
            <Spinner size="medium" accessibilityLabel="Status Change In Process" />
          </Button>
        ) : (
          <IconButton
            icon={TrashIcon}
            size="medium"
            onClick={handleDeleteSchedule}
            accessibilityLabel="Delete Schedule"
          />
        )}
      </SchedulesTableIconControlContainer>
    );
  },
);

export const ControlActions = (props) => {
  const dashboardType = useDashboardType();
  return <ControlActionsComponent {...props} dashboardType={dashboardType} />;
};
