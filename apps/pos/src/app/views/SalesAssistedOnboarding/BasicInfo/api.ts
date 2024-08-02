import { merchantFetch } from '@dashboard/shared-utils/ajax';

type ApiResponseType = {
  data?: {
    id: string;
    name: string;
  };
  status_code: number;
  success: boolean;
  errors?: unknown;
};

export const saveUserName = (payload: { name: string }): Promise<ApiResponseType> => {
  return merchantFetch({
    url: 'users/update_name',
    method: 'post',
    data: payload,
  });
};
