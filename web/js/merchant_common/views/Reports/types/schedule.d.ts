export enum ScheduleStatus {
  paused = 'paused',
  active = 'active',
  finished = 'finished',
}

export interface BaseScheduleType {
  created_by: string;
  id?: string;
  name: string;
  period: string;
  consumer?: string;
  config_id: string;
  config_name?: string;
  emails: string[];
  template_overrides?: {
    file_meta?: {
      extension?: string;
      filename?: string;
    };
  };
  task?: {
    minute: {
      start: number;
    };
    hour: {
      start: number;
    };
    day: {
      start: number;
    };
    month: {
      start: number;
    };
    week: {
      start: number;
    };
    type: string;
  };
  status?: keyof typeof ScheduleStatus;
  schedule_entity_id?: string;
}
export interface ScheduleType extends BaseScheduleType {
  hour?: number;
  minute?: number;
  delay?: number;
  month?: number;
  day?: number;
  created_at?: number;
  updated_at?: number;
  schedule_start_time: number;
  schedule_end_time: number;
  interval?: number;
}

// limitation from BE
export interface ScheduleServerPayload extends BaseScheduleType {
  hour?: string;
  minute?: string;
  delay?: string;
  month?: string;
  day?: string;
  created_at?: string;
  updated_at?: string;
  schedule_start_time: string;
  schedule_end_time: string;
  interval?: string;
}
