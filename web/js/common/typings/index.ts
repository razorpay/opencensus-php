import { useFormik } from 'formik';

export { default as User } from './User';
export * from './Store';

export type CommonApiResponse<T, ErrorType = unknown> = {
  data?: T;
  status_code: number;
  success: boolean;
  errors?: ErrorType;
};

export type PaginationParamsType = { skip: number; count: number };

export type UseFormikReturnType = ReturnType<typeof useFormik>;
export type FormikHandleChange = (args: {
  name?: string;
  value?: boolean | string | number;
}) => void;
