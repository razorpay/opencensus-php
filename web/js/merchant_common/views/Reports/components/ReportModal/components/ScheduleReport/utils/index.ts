import {
  getDataDurations,
  getRepetitions,
} from 'merchant_common/views/Reports/components/ReportModal/components/ScheduleReport/data';
import { ScheduleType } from 'merchant_common/views/Reports/types/schedule';
import moment from 'moment';

export const getWhenScheduleDetails = ({ whenTime, selectedRepetition, selectedDataDuration }) => {
  if (!whenTime) return {};

  const baseWhenScheduleConfig = {
    interval: 1,
    day: 0,
    delay: 0,
    month: 0,
    hour: +whenTime?.date.format('HH') ?? 0,
    minute: +whenTime?.date.format('mm') ?? 0,
    task: {
      minute: {
        start: 0,
      },
      hour: {
        start: 0,
      },
      day: {
        start: 0,
      },
      month: {
        start: 0,
      },
      week: {
        start: 0,
      },
      type: selectedRepetition?.value,
    },
  };

  switch (selectedDataDuration?.value) {
    case 'same_day':
      return {
        ...baseWhenScheduleConfig,
        task: {
          ...baseWhenScheduleConfig.task,
          day: {
            start: 0,
          },
        },
      };
    case 'previous_day':
      return {
        ...baseWhenScheduleConfig,
        task: {
          ...baseWhenScheduleConfig.task,
          day: {
            start: -1,
          },
        },
      };
    case 'same_week':
      return {
        ...baseWhenScheduleConfig,
        day: selectedRepetition?.weekIndex ?? 0,
        task: {
          ...baseWhenScheduleConfig.task,
          week: {
            start: 0,
          },
        },
      };
    case 'previous_week':
      return {
        ...baseWhenScheduleConfig,
        day: selectedRepetition?.weekIndex ?? 0,
        task: {
          ...baseWhenScheduleConfig.task,
          week: {
            start: -1,
          },
        },
      };
    case 'same_month':
      return {
        ...baseWhenScheduleConfig,
        day: selectedRepetition?.dateIndex ?? 0,
        task: {
          ...baseWhenScheduleConfig.task,
          month: {
            start: 0,
          },
        },
      };
    case 'previous_month':
      return {
        ...baseWhenScheduleConfig,
        day: selectedRepetition?.dateIndex ?? 0,
        task: {
          ...baseWhenScheduleConfig.task,
          month: {
            start: -1,
          },
        },
      };
    case 'previous_quarter':
      return {
        ...baseWhenScheduleConfig,
        interval: 3,
        task: {
          ...baseWhenScheduleConfig.task,
          month: {
            start: -3,
            end: -1,
          },
        },
      };
    default:
      return baseWhenScheduleConfig;
  }
};

export const checkSelectedDataDuration = (matchWith: string, dayIndex?: number) => {
  const shouldCompareDay = ['same_week', 'previous_day', 'same_month', 'previous_month'].includes(
    matchWith,
  );

  // check if duration exist without custom bool
  const isMatchedWithoutCustom = getDataDurations(false).find((e) => e.value === matchWith);
  if (isMatchedWithoutCustom && (shouldCompareDay ? dayIndex === 0 : true))
    return {
      isCustomEnabled: false,
      selectedDataDuration: isMatchedWithoutCustom,
    };

  // check if duration exist with custom bool
  const isMatchedWithCustom = getDataDurations(true).find((e) => e.value === matchWith);
  if (isMatchedWithCustom)
    return {
      isCustomEnabled: true,
      selectedDataDuration: isMatchedWithCustom,
    };

  return null;
};

export const getSelectedDataDuration = (scheduleData: ScheduleType) => {
  const whenTimeMoment = moment().set({
    hour: scheduleData.hour,
    minute: scheduleData.minute,
  });
  const whenTime = {
    date: whenTimeMoment,
    renderInfo: {
      hour: scheduleData.hour!,
      meridiem: whenTimeMoment.format('A') as 'AM' | 'PM',
      minutes: scheduleData.minute!,
    },
  };

  switch (true) {
    case scheduleData?.task?.day?.start === 0 && scheduleData?.task.type === 'daily':
      return {
        whenTime,
        ...checkSelectedDataDuration('same_day'),
      };
    case scheduleData?.task?.day?.start === -1 && scheduleData?.task.type === 'daily':
      return {
        whenTime,
        ...checkSelectedDataDuration('previous_day'),
      };
    // day is left, for selectedRepetition
    case scheduleData?.task?.week?.start === 0 && scheduleData?.task.type === 'weekly':
      return {
        whenTime,
        ...checkSelectedDataDuration('same_week', scheduleData?.day),
      };
    case scheduleData?.task?.week?.start === -1 && scheduleData?.task.type === 'weekly':
      return {
        whenTime,
        ...checkSelectedDataDuration('previous_week', scheduleData?.day),
      };
    case scheduleData?.task?.month?.start === 0 && scheduleData?.task.type === 'monthly':
      return {
        whenTime,
        ...checkSelectedDataDuration('same_month', scheduleData?.day),
      };
    case scheduleData?.task?.month?.start === -1 && scheduleData?.task.type === 'monthly':
      return {
        whenTime,
        ...checkSelectedDataDuration('previous_month', scheduleData?.day),
      };
    case scheduleData?.task?.month?.start === -3 &&
      scheduleData?.interval === 3 &&
      scheduleData?.task.type === 'monthly' &&
      scheduleData?.task?.month?.end === -1:
      return {
        whenTime,
        ...checkSelectedDataDuration('previous_quarter'),
      };
    default:
      return null;
  }
};

export const reverseScheduleData = (schedule: ScheduleType) => {
  const data = getSelectedDataDuration(schedule);

  if (!data || !data.selectedDataDuration || !data.whenTime) return null;

  const { isCustomEnabled, selectedDataDuration } = data;

  const selectedRepetition = getRepetitions(selectedDataDuration?.value, isCustomEnabled).find(
    (e: { label: string; value: string; weekIndex?: number; dateIndex?: number }) => {
      const isSameType = e.value === schedule?.task?.type;
      const shouldCompareDay = ['weekly', 'monthly'].includes(schedule?.task?.type ?? '');

      switch (true) {
        case shouldCompareDay && schedule?.task?.type === 'weekly':
          return isSameType && schedule?.day === e.weekIndex;
        case shouldCompareDay && schedule.task?.type === 'monthly':
          return isSameType && schedule?.day === e.dateIndex;
        default:
          return isSameType;
      }
    },
  );

  if (!selectedRepetition) return null;

  return {
    ...data,
    selectedRepetition,
  };
};
