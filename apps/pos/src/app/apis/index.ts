import { merchantFetch } from '@libs/web-nexus/merchant/utils/merchantFetch';

interface SalesFetchProps<T> {
  url: string;
  method: 'GET' | 'POST' | 'PUT' | 'DELETE';
  mode: 'live' | 'test';
  data?: T;
  isAbsUrl?: boolean;
  headers?: Record<string, string>;
}

export const salesFetch = <SalesFetchArgs, APIResponse>({
  url,
  method,
  mode,
  data,
  isAbsUrl,
  headers,
}: SalesFetchProps<SalesFetchArgs>): Promise<APIResponse> =>
  // eslint-disable-next-line @typescript-eslint/no-unsafe-call
  merchantFetch({
    url,
    method,
    mode,
    data,
    absUrl: isAbsUrl ? url : undefined,
    ...(headers ? headers : {}),
  });
