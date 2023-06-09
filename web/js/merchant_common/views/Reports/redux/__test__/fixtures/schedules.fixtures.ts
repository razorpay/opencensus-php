import { mockConfigs } from './configs.fixtures';

export const mockSchedules = [
  {
    id: 'sched_Lv8DGvlaMJWo31',
    config_id: mockConfigs[0].id,
    config_name: mockConfigs[0].name,
    period: 'daily',
    interval: '1',
    day: '0',
    hour: '12',
    minute: '30',
    delay: '0',
    month: '0',
    created_by: '100000Razorpay',
    next_run_at: '1685343600',
    last_run_at: '0',
    created_at: '1685287530',
    updated_at: '1685287530',
    schedule_start_time: '1685440481',
    schedule_end_time: '1697900331',
    consumer: 'Fx8KHLQClpbeKN',
    task: {
      day: {
        start: -1,
      },
      hour: {
        start: 0,
      },
      type: 'daily',
      month: {
        start: 0,
      },
      minute: {
        start: 0,
      },
      week: {
        start: 0,
      },
    },
    scheduler_entity_id: 'Lv8DAAREryZ77l',
    emails: ['unactivated@gmail.com'],
    name: 'Schedule At 5pm daily',
    template_overrides: {
      file_meta: {
        extension: 'csv',
        filename: 'Testing',
      },
    },
  },
];
