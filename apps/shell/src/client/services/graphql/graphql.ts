import { getMode, getUser, getOrg } from '@federated/apps/shell/commonStore';
import { GraphQLClient, Variables } from 'graphql-request';
import { print, DocumentNode } from 'graphql';
import { GraphQLClientResponse } from 'graphql-request/build/esm/types';
import { getCookie, getPhpBaseUrlForClient } from '@libs/shared-utils';
import { RazorpayUser, DASHBOARD_MODE } from '@libs/shared-types';
import { IS_PRODUCTION } from '@apps/shell/src/env';

const graphqlEndpoint = `${getPhpBaseUrlForClient()}/graph`;


/**
 * Interface for options passed to a GraphQL request function.
 */
type GraphQLRequestFnOptions = {
  document: DocumentNode;
  variables?: Variables;
};

/**
 * Represents the structure of an Organization object.
 */
type Org = {
  id: string;
};

/**
 * Type definition for GraphQL errors.
 */
type GraphQLError = {
  message: string;
  extensions?: Record<string, any>;
  locations?: { line: number; column: number }[];
  path?: string[];
};

/**
 * Middleware function to inject headers into GraphQL requests.
 * This includes headers like `XSRF-TOKEN`, user info, org info, and dashboard mode.
 *
 * @param request - The original GraphQL request object.
 * @returns The modified request object with additional headers.
 */
const requestMiddleware = (request: any) => {
  const xsrfToken = getCookie('XSRF-TOKEN');
  const user = getUser() as RazorpayUser;
  const org = getOrg() as Org;
  const mode = getMode() as DASHBOARD_MODE;

  return {
    ...request,
    headers: {
      ...request?.headers,
      'Content-Type': 'application/json',
      'apollographql-client-name':
        request?.headers?.['apollographql-client-name'] ?? 'merchant-dashboard',
      ...(IS_PRODUCTION ? { 'apollographql-client-version': window.__VERSION__ } : {}),
      'x-app-mode': mode,
      'x-dashboard-user-id': user?.user?.id,
      'x-dashboard-merchant-id': user?.current,
      'x-org-id': org?.id,
      ...(xsrfToken ? { 'x-xsrf-token': xsrfToken } : {}),
    },
  };
};

/**
 * GraphQLClient instance with the request middleware to handle request headers.
 */
const graphqlClient = new GraphQLClient(graphqlEndpoint, {
  requestMiddleware,
});

/**
 * Function to perform a GraphQL request with query or mutation.
 *
 * @template QueryKey - The string literal type of the query key.
 * @template SuccessResponse - The type of a successful response.
 * @template ErrorResponse - The type of an error response.
 *
 * @param options - The options containing the GraphQL document and variables.
 * @returns A promise resolving to a record with the query key and the response data.
 * @throws An error if the request fails.
 */
const graphqlRequest = async <QueryKey extends string, SuccessResponse, ErrorResponse>({
  document,
  variables,
}: GraphQLRequestFnOptions): Promise<Record<QueryKey, SuccessResponse | ErrorResponse>> => {
  const operationDocumentInString = print(document);
  try {
    const response: GraphQLClientResponse<Record<QueryKey, SuccessResponse | ErrorResponse>> =
      await graphqlClient.rawRequest(operationDocumentInString, variables);

    const csrfToken = response.headers.get('x-csrf-token');
    if (csrfToken) {
      graphqlClient.setHeader('X-Csrf-token', csrfToken);
    }

    return response.data;
  } catch (error: any) {
    let operationName = '';
    if (document.definitions[0] && 'name' in document.definitions[0]) {
      operationName = document?.definitions?.[0]?.name?.value as string;
    }

    if (error.name === 'TypeError') {
      // Handle network request failure or server issues.
    } else {
      // Handle errors returned by the GraphQL server.
      const errors: GraphQLError[] = error?.response?.errors || [];
      errors.forEach(({ extensions, message }) => {
        console.log('Trigger sentry exception here with', {
          extensions,
          message,
          operationName,
          variables, // Exclude PII data
        });
      });
    }

    throw error;
  }
};

/**
 * A helper function to make a GraphQL query request using a query key.
 *
 * @param queryKey - The query key, which includes the document and variables.
 * @returns A promise with the GraphQL request result.
 */
const graphqlRequestQuery = ({ queryKey }: { queryKey: [DocumentNode, Variables] }) => {
  const [document, variables] = queryKey;
  return graphqlRequest({ document, variables });
};

/**
 * A helper function to make a GraphQL mutation request.
 *
 * @param document - The GraphQL mutation document.
 * @param variables - The variables for the mutation.
 * @returns A promise with the GraphQL request result.
 */
const graphqlRequestMutation = ({ document, variables }: GraphQLRequestFnOptions) => {
  return graphqlRequest({ document, variables });
};

export { graphqlRequestQuery, graphqlRequestMutation, graphqlRequest, graphqlClient };
