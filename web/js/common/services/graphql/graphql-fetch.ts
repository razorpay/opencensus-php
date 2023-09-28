/* eslint-disable @typescript-eslint/no-explicit-any */
import { print } from 'graphql/language/printer';
import { DocumentNode } from 'graphql';
import axios, { AxiosRequestConfig, AxiosError } from 'axios';

export const axiosInstance = axios.create({});

const isAxiosResponse = <T>(obj: any): obj is AxiosError<T> =>
  typeof obj === 'object' && typeof obj.request === 'object';

export type Variables = { [key: string]: any };

export interface GraphQLError {
  message: string;
  locations: { line: number; column: number }[];
  path: string[];
}

export interface GraphQLResponse {
  data?: any;
  errors?: GraphQLError[];
  extensions?: any;
  status: number;
  [key: string]: any;
}

export interface GraphQLRequestContext {
  query: string;
  variables?: Variables;
}

export class ClientError extends Error {
  response: GraphQLResponse;
  request: GraphQLRequestContext;

  constructor(response: GraphQLResponse, request: GraphQLRequestContext) {
    const message = ClientError.extractMessage(response);
    super(message);
    this.response = response;
    this.request = request;

    // this is needed as Safari doesn't support .captureStackTrace
    if (typeof (Error as any).captureStackTrace === 'function') {
      (Error as any).captureStackTrace(this, ClientError);
    }
  }

  private static extractMessage(response: GraphQLResponse): string {
    try {
      // eslint-disable-next-line @typescript-eslint/no-non-null-assertion
      return response.errors![0].message;
    } catch (e) {
      return `GraphQL Error (Code: ${response.status})`;
    }
  }
}

// eslint-disable-next-line @typescript-eslint/ban-types
export async function fetchGraphQL<T extends any, V extends object>(
  query: string | DocumentNode,
  variables?: V,
  url = '/graph',
  options?: AxiosRequestConfig,
): Promise<T> {
  const printedQuery = typeof query === 'string' ? query : print(query);
  const data = JSON.stringify({
    query: printedQuery,
    variables: variables ? variables : undefined,
  });
  try {
    const response = await axiosInstance({
      url,
      method: 'POST',
      data,
      ...options,
      headers: {
        'Content-Type': 'application/json',
        'apollographql-client-name': 'dashboard',
        'apollographql-client-version': window.__VERSION__,
        ...options?.headers,
      },
    });
    const result = response.data;
    if (response.status >= 200 && response.status <= 204 && result.data) {
      return result.data;
    } else {
      const errorResult = typeof result === 'string' ? { error: result } : result;
      throw new ClientError(
        { ...errorResult, status: response.status },
        { query: printedQuery, variables },
      );
    }
  } catch (e: any) {
    if (e instanceof ClientError) {
      throw e;
    } else if (isAxiosResponse(e)) {
      throw new ClientError({ ...e.response, status: 400 }, { query: printedQuery, variables });
    } else {
      throw new ClientError({ ...e, status: '400' }, { query: printedQuery, variables });
    }
  }
}
