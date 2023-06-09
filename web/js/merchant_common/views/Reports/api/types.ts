export interface ResPayload<T> {
  count?: number;
  entity?: string;
  total_count?: number;
  items: T[];
}

export interface ResType<PayloadType> {
  status_code?: number;
  success?: boolean;
  data: PayloadType;
}

export interface ErrorType {
  name: string;
  message: string;
}

export interface PollResFailedCallbackArgs {
  err?: ErrorType;
}

export interface LongPollParamType<T> {
  fetchFunc: () => Promise<ResType<ResPayload<T>>>;
  validator: (x: ResPayload<T>) => boolean;
  pollResSuccessCallback: (
    x: ResPayload<T>,
    y: {
      polling: boolean;
    },
  ) => void;
  pollResFailedCallback: (x: PollResFailedCallbackArgs) => void;
  onPollStopCallback: () => void;
}

export interface LongPollReturnType<T> {
  promise: Promise<T>;
  abort: () => void;
}

export type ReportsFetchHeaders = Record<string, string>;

export interface LongPollInitiatorArgs<T> {
  queryParams: ReportsFetchAPIParams;
  headers: ReportsFetchHeaders;
  pollResSuccessCallback: (x: ResPayload<T>) => void;
  pollResFailedCallback: (x: PollResFailedCallbackArgs) => void;
  onPollStopCallback: () => void;
}

export interface ReportsFetchAPIParams {
  page: number;
  filter: string;
  scheduleId?: string;
}

export interface ScheduleAPIFnParams {
  headers: ReportsFetchHeaders;
  scheduleId: string;
}

export enum CreateScheduleMethod {
  'post' = 'post',
  'patch' = 'patch',
}

export interface CreateScheduleAPIFnParams<T> {
  headers: ReportsFetchHeaders;
  payload: T;
  method: keyof typeof CreateScheduleMethod;
  scheduleId: string;
}
export interface ScheduleAPIEditParams<T> extends ScheduleAPIFnParams {
  payload: T;
}
