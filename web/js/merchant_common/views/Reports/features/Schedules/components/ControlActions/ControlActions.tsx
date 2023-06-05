import React from 'react';
import { connect } from 'react-redux';
import { openModal } from 'merchant_common/reducers/modals';
import { IconProps, IconComponent } from '@razorpay/blade/components';
import { useDashboardType } from 'merchant_common/views/Reports/contexts/ReportsContext';
import { SchedulesTableIconControlContainer } from 'merchant_common/views/Reports/features/Schedules/styled';
import {
  PauseIcon,
  TrashIcon,
  PlayIcon,
  IconButton,
  ReportModal,
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
import { ControlActionsArgs } from './types';
import { ScheduleAPIFnParams } from 'merchant_common/views/Reports/api/types';

const mapDispatchToProps = (dispatch) => ({
  openModal: (modal) => dispatch(openModal(modal)),
  showNotification: (payload) => dispatch(showNotification(payload)),
});

export const ControlActions = connect(
  null,
  mapDispatchToProps,
)(({ openModal, scheduleData: { status, id }, showNotification }: ControlActionsArgs) => {
  const dashboardType = useDashboardType();

  const handleControlAction = <T,>({
    action,
    icon,
    promise,
  }: {
    action: 'pause' | 'delete' | 'resume';
    icon: ((x: IconProps) => React.ReactElement) | IconComponent;
    promise: (x: ScheduleAPIFnParams) => Promise<void>;
  }) => {
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
                    .finally(() => {});
                },
                icon,
                label: `${action[0].toUpperCase() + action.slice(1)} Schedule`,
              },
              alert: {
                intent:
                  action === 'delete' ? 'negative' : action === 'pause' ? 'information' : 'notice',
                description: `You are about to ${action} a schedule with id as ${id}.`,
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

  return (
    <SchedulesTableIconControlContainer>
      {status === 'active' ? (
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

      <IconButton
        icon={TrashIcon}
        size="medium"
        onClick={handleDeleteSchedule}
        accessibilityLabel="Delete Schedule"
      />
    </SchedulesTableIconControlContainer>
  );
});
