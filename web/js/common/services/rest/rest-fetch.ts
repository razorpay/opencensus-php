// Todo: delete this file, it's available in @dashboard/shared-utils
import axios, { AxiosRequestConfig, AxiosError } from 'axios';

import { getMode, ModeT } from 'common/services/mode';
export const restInstance = axios.create({});

const isAxiosResponse = <T>(obj: any): obj is AxiosError<T> =>
  typeof obj === 'object' && typeof obj.request === 'object';

interface Response {
  data?: any;
  errors?: any;
  status: number;
}

class ClientError extends Error {
  response: Response;

  constructor(response: Response) {
    const message = ClientError.extractMessage(response);
    super(message);
    this.response = response;

    // this is needed as Safari doesn't support .captureStackTrace
    if (typeof (Error as any).captureStackTrace === 'function') {
      (Error as any).captureStackTrace(this, ClientError);
    }
  }

  private static extractMessage(response: Response): string {
    try {
      // eslint-disable-next-line @typescript-eslint/no-non-null-assertion
      return response.errors![0].message;
    } catch (e) {
      return `Error (Code: ${response.status})`;
    }
  }
}

// eslint-disable-next-line @typescript-eslint/ban-types
export async function fetch<T extends any>(
  options: AxiosRequestConfig & { mode?: ModeT },
): Promise<T> {
  try {
    const response = await restInstance({
      ...options,
      url: `/merchant/api/${typeof options.mode !== 'undefined' ? options.mode : getMode()}/${
        options.url
      }`,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json, text/plain, */*',
        ...options?.headers,
      },
    });
    const result = response.data;
    if (response.status >= 200 && response.status <= 204 && typeof result.data !== 'undefined') {
      return result.data;
    } else {
      const errorResult = typeof result === 'string' ? { error: result } : result;
      throw new ClientError({ ...errorResult, status: response.status });
    }
  } catch (e: any) {
    if (e.response.status === 401) {
      document.body.dispatchEvent(
        new CustomEvent('NOT_AUTHENTICATED', {
          bubbles: true,
          detail: { continueAjax: () => {} },
        }),
      );
    }
    if (e instanceof ClientError) {
      throw e;
    } else if (isAxiosResponse(e)) {
      throw e;
    } else {
      throw new ClientError({ ...e, status: '400' });
    }
  }
}

// eslint-disable-next-line @typescript-eslint/ban-types
export async function fetchUCS<T extends any>(
  options: AxiosRequestConfig & { mode?: ModeT },
): Promise<T> {
  try {
    const response = await restInstance({
      ...options,
      url: `/ucs/${options.url}`,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept: 'application/json, text/plain, */*',
        'X-Razorpay-Mode': getMode(),
        'X-Razorpay-Merchant-Id': window.rzp_user.current,
        ...options?.headers,
      },
    });
    const result = response.data;
    if (response.status >= 200 && response.status <= 204 && result) {
      return result;
    } else {
      const errorResult =
        typeof result === 'string' ? { error: result } : result?.error ? result.error : result;
      throw new ClientError({ ...errorResult, status: response.status });
    }
  } catch (e: any) {
    if (e?.response?.status === 401) {
      document.body.dispatchEvent(
        new CustomEvent('NOT_AUTHENTICATED', {
          bubbles: true,
          detail: { continueAjax: () => {} },
        }),
      );
    }
    if (e instanceof ClientError) {
      throw e;
    } else if (isAxiosResponse(e)) {
      throw e;
    } else {
      throw new ClientError({ ...e, status: '400' });
    }
  }
}
