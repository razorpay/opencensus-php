import { ScheduleServerPayload, ScheduleType } from 'merchant_common/views/Reports/types/schedule';

export const changeScheduleStringsToNumerics = (payload: ScheduleServerPayload) => {
  return {
    day: payload?.day ? +payload.day : undefined,
    delay: payload?.delay ? +payload.delay : undefined,
    hour: payload?.hour ? +payload.hour : undefined,
    minute: payload?.minute ? +payload.minute : undefined,
    month: payload?.month ? +payload.month : undefined,
    schedule_end_time: +payload?.schedule_end_time,
    schedule_start_time: +payload?.schedule_start_time,
    interval: payload?.interval ? +payload.interval : undefined,
    updated_at: payload?.updated_at ? +payload.updated_at : undefined,
    created_at: payload?.created_at ? +payload.created_at : undefined,
  };
};

export const changeScheduleNumericsToString = (payload: ScheduleType) => {
  return {
    day: payload?.day?.toString(),
    delay: payload?.delay?.toString(),
    hour: payload?.hour?.toString(),
    minute: payload?.minute?.toString(),
    month: payload?.month?.toString(),
    schedule_end_time: payload?.schedule_end_time?.toString(),
    schedule_start_time: payload?.schedule_start_time?.toString(),
    interval: payload?.interval?.toString(),
    updated_at: payload?.updated_at?.toString(),
    created_at: payload?.created_at?.toString(),
  };
};
