export type APIResponse<T, E> = {
  status_code: number;
  success: boolean;
  data?: T;
  errors?: E[];
  code?: string;
};
