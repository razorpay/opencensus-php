import { GraphQLClient, Variables } from 'graphql-request';
import { print, DocumentNode } from 'graphql';
import { getMode } from 'common/services/mode';
import { getCookie } from 'common/utils/cookies';

const isProd = process.env.STAGE == 'production';

/**
 * /graph is the dashboard route which will internally route the requests to graphql server
 **/
const GRAPHQL_SERVER_HOST = '/graph';

type GraphQLRequestFn = (options: {
  document: DocumentNode;
  variables?: Variables;
}) => Promise<any>;

type GraphqlErrorResponse = {
  response: { errors: Array<{ message?: string }> | Array<string> };
};

const requestMiddleware = (request) => {
  const xsrfToken = getCookie('XSRF-TOKEN');

  return {
    ...request,
    headers: {
      ...request.headers,
      'Content-Type': 'application/json',
      'apollographql-client-name': 'merchant-dashboard',
      //version/commitid is added only in production
      ...(isProd ? { 'apollographql-client-version': window.__VERSION__ } : {}),
      'x-app-mode': getMode(),
      ...(xsrfToken ? { 'x-xsrf-token': xsrfToken } : {}),
    },
  };
};

const graphqlClient = new GraphQLClient(GRAPHQL_SERVER_HOST, {
  requestMiddleware,
});

const graphqlRequest: GraphQLRequestFn = async ({ document, variables }) => {
  const operationDocumentInString = print(document);
  try {
    const response = await graphqlClient.rawRequest(operationDocumentInString, variables);
    const csrfToken = response.headers.get('x-csrf-token');

    if (csrfToken) {
      //set cookie here as well?
      graphqlClient.setHeader('X-Csrf-token', csrfToken);
    }

    return response.data;
  } catch (error: any) {
    let operationName = '';
    if (document.definitions[0] && 'name' in document.definitions[0]) {
      operationName = (document.definitions[0] as any).name.value; // Using 'as any' to handle type checking
      // Continue with the operationName
    }

    if (error.name == 'TypeError') {
      // NetworkRequest Failed OR server is down OR client not able to communicate
    } else {
      //Errors thrown by the server
      //if (error?.response) {
      //TODO: Check if GQL server errors are to be captured on sentry?
      const { errors } = error?.response;
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

export { graphqlRequestQuery, graphqlRequestMutation, graphqlRequest, GraphqlErrorResponse };
export default graphqlClient;
