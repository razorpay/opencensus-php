export type ScheduleData = {
  method: null | string;
  international: 0 | 1;
  delay: number;
};

export type SettlementReducerState = {
  loading: boolean;
  schedule: {
    loading: boolean;
    data: ScheduleData[];
    error: null | string;
  };
};
