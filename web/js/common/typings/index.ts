export { default as User } from './User';
export * from './Store';

export type CommonApiResponse<T> = {
  data?: T;
  status_code: number;
  success: boolean;
};
