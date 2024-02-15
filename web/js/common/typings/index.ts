import { useFormik } from 'formik';
import * as Yup from 'yup';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';

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

export type DataTableColumn<T = TODO_PD> = {
  title: JSX.Element | string;
  value: (item: T) => JSX.Element | string;
};
export type DataTableColumns = Array<DataTableColumn>;

export type YupObjectSchema = Yup.ObjectSchema;
