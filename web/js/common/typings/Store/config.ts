import { CommonApiResponse } from 'common/typings';

export type FetchFeatureStatusType = (
  userId: string,
  string,
) => Promise<CommonApiResponse<{ status: boolean }>>;
