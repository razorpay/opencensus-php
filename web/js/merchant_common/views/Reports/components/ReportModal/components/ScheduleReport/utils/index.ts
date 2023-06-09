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
    case 'past_24_hours':
      return {
        ...baseWhenScheduleConfig,
        task: {
          ...baseWhenScheduleConfig.task,
          day: {
            start: -1,
          },
        },
      };
    case 'past_2_days':
      return {
        ...baseWhenScheduleConfig,
        task: {
          ...baseWhenScheduleConfig.task,
          day: {
            start: -2,
          },
        },
      };
    case 'past_3_days':
      return {
        ...baseWhenScheduleConfig,
        task: {
          ...baseWhenScheduleConfig.task,
          day: {
            start: -3,
          },
        },
      };
    case 'past_week':
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

    case 'past_15_days':
      return {
        ...baseWhenScheduleConfig,
        interval: 15,
        task: {
          ...baseWhenScheduleConfig.task,
          day: {
            start: -15,
          },
        },
      };
    case 'past_month':
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
    case 'past_quater':
      return {
        ...baseWhenScheduleConfig,
        interval: 3,
        task: {
          ...baseWhenScheduleConfig.task,
          month: {
            start: -3,
          },
        },
      };
    default:
      return baseWhenScheduleConfig;
  }
};

export const checkSelectedDataDuration = (matchWith: string) => {
  // check if duration exist without custom bool
  const isMatchedWithoutCustom = getDataDurations(false).find((e) => e.value === matchWith);
  if (isMatchedWithoutCustom)
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
    case scheduleData?.task?.day?.start === -1:
      return {
        whenTime,
        ...checkSelectedDataDuration('past_24_hours'),
      };
    case scheduleData?.task?.day?.start === -2:
      return {
        whenTime,
        ...checkSelectedDataDuration('past_2_days'),
      };
    case scheduleData?.task?.day?.start === -3:
      return {
        whenTime,
        ...checkSelectedDataDuration('past_3_days'),
      };
    // day is left, for selectedRepetition
    case scheduleData?.task?.week?.start === -1:
      return {
        whenTime,
        ...checkSelectedDataDuration('past_week'),
      };
    case scheduleData?.task?.day?.start === -15 && scheduleData?.interval === 15:
      return {
        whenTime,
        ...checkSelectedDataDuration('past_15_days'),
      };
    // day is left, for selectedRepetition
    case scheduleData?.task?.month?.start === -1:
      return {
        whenTime,
        ...checkSelectedDataDuration('past_month'),
      };
    case scheduleData?.task?.month?.start === -3 && scheduleData?.interval === 3:
      return {
        whenTime,
        ...checkSelectedDataDuration('past_quater'),
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
    (e) => e.value === schedule?.task?.type,
  );

  if (!selectedRepetition) return null;

  return {
    ...data,
    selectedRepetition,
  };
};
