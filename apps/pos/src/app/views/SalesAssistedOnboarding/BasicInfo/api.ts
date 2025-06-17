import { posFetch } from 'apps/pos/src/app/utils/posFetch';

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
  return posFetch({
    url: 'users/update_name',
    method: 'post',
    data: payload,
  });
};
