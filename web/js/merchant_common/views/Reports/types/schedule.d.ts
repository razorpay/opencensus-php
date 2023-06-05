enum ScheduleStatus {
  paused = 'paused',
  active = 'active',
}

export interface ScheduleType {
  id: string;
  name: string;
  period: string;
  hour: number;
  minute: number;
  delay: number;
  month: number;
  created_by: string;
  created_at?: number;
  updated_at?: number;
  consumer?: string;
  config_id: string;
  config_name: string;
  scheduleStartTime: number;
  scheduleEndTime: number;
  template_overrides: {
    file_meta: {
      extension: string;
      filename: string;
    };
  };
  emails: string[];
  task: {
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
    type: string;
  };
  status: keyof typeof ScheduleStatus;
  schedule_entity_id?: string;
}
