import { getMode, getUser, getOrg } from 'shell/commonStore';
import { GraphQLClient, Variables } from 'graphql-request';
import { print, DocumentNode } from 'graphql';
import { GraphQLClientResponse } from 'graphql-request/build/esm/types';
import { getCookie } from '../cookies';
import { User, Environments } from '../typings';

const isProd = process.env['STAGE'] == 'production';
const domain = window.location.hostname;
const protocol = window.location.protocol;
const graphqlEndpoint =
  domain === 'localhost'
    ? // eslint-disable-next-line i18n-rules/no-hardcoded-i18n-types
      'https://dashboard.dev.razorpay.in/graph'
    : `${protocol}//${domain}/graph`;

declare global {
  interface Window {
    __VERSION__: string;
  }
}

type GraphQLRequestFnOptions = {
  document: DocumentNode;
  variables?: Variables;
};

type Org = {
  id: string;
};

type GraphqlErrorResponse = {
  response: { errors: Array<{ message?: string }> | Array<string> };
};

const requestMiddleware = (request) => {
  const xsrfToken = getCookie('XSRF-TOKEN');
  const user = getUser() as User;
  const org = getOrg() as Org;
  const mode = getMode() as Environments;

  return {
    ...request,
    headers: {
      ...request?.headers,
      'Content-Type': 'application/json',
      'apollographql-client-name': 'merchant-dashboard',
      ...(isProd ? { 'apollographql-client-version': window.__VERSION__ } : {}),
      'x-app-mode': mode,
      'x-dashboard-user-id': user?.user?.id,
      'x-dashboard-merchant-id': user?.current,
      'x-org-id': org?.id,
      ...(xsrfToken ? { 'x-xsrf-token': xsrfToken } : {}),
    },
  };
};

const graphqlClient = new GraphQLClient(graphqlEndpoint, {
  requestMiddleware,
});

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
      //set cookie here as well?
      graphqlClient.setHeader('X-Csrf-token', csrfToken);
    }

    return response.data;
  } catch (error: any) {
    let operationName = '';
    if (document.definitions[0] && 'name' in document.definitions[0]) {
      operationName = document?.definitions?.[0]?.name?.value as string;
      // Continue with the operationName
    }

    if (error.name == 'TypeError') {
      // NetworkRequest Failed OR server is down OR client not able to communicate
    } else {
      //Errors thrown by the server
      //if (error?.response) {
      //TODO: Check if GQL server errors are to be captured on sentry?
      const errors = error?.response?.errors || [];
      errors.forEach(({ extensions, message }) => {
        //TODO: Check if GQL server errors are to be captured on sentry?
        console.log('Trigger sentry exception here with', {
          extensions,
          message,
          operationName,
          variables, // exclude PII data
        });
      });
      // }
    }

    throw error;
  }
};

const graphqlRequestQuery = ({ queryKey }) => {
  const [document, variables] = queryKey;
  return graphqlRequest({ document: document as DocumentNode, variables });
};

const graphqlRequestMutation = ({ document, variables }) => {
  return graphqlRequest({ document, variables });
};

export {
  graphqlRequestQuery,
  graphqlRequestMutation,
  graphqlRequest,
  graphqlClient,
  GraphqlErrorResponse,
};
