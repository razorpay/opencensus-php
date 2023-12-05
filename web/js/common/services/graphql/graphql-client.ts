import { GraphQLClient, Variables } from 'graphql-request';
import { print, DocumentNode } from 'graphql';

const GRAPHQL_SERVER_HOST = 'http://localhost:8888'; // replace with /graph for prod

type GraphQLRequestFn = (options: {
  document: DocumentNode;
  variables?: Variables;
}) => Promise<any>;

const graphqlClient = new GraphQLClient(GRAPHQL_SERVER_HOST, {
  headers: {
    'Content-Type': 'application/json',
    'apollographql-client-name': 'dashboard',
    // 'apollographql-client-version': '' , //TODO: check how to use the deployed app version
  },
});

const graphqlRequest: GraphQLRequestFn = async ({ document, variables }) => {
  const operationDocumentInString = print(document);
  try {
    const response = await graphqlClient.rawRequest(operationDocumentInString, variables);
    const csrfToken = response.headers.get('x-csrf-token');
    if (csrfToken) {
      graphqlClient.setHeader('X-Csrf-token', csrfToken);
    }

    return response;
  } catch (error: any) {
    //TODO: Add logic to capture errors

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
      //TODO: trigger sentry exception capture with following variables
      const { errors } = error?.response;
      errors.forEach(({ extensions, message }) => {
        //TODO: trigger sentry excepture capture with following variables
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

export { graphqlRequestQuery, graphqlRequestMutation };
export default graphqlClient;
