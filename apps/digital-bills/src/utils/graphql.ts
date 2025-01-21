// Import and re-export the necessary methods from '@dashboard/shared-utils/graphql/graphql'
// All imports for graphql utilities inside '@apps/digital-bills/src' should be done from this file
// This file should be deleted after full shell roll-out and import statements should be updated to point to the shell

export {
  graphqlClient,
  graphqlRequestQuery,
  graphqlRequestMutation,
  graphqlRequest,
} from '@dashboard/shared-utils/graphql/graphql';
