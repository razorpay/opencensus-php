export const verifyGqlErrorResponse = (graphQLResponse) => {
  if (graphQLResponse?.response?.errors) {
    const errorMessage = graphQLResponse.response.errors[0]?.message;
    const errorStatus = graphQLResponse.response.errors[0]?.extensions?.code;
    if (errorStatus === 'FORBIDDEN' && errorMessage === 'Access is forbidden') {
      return true;
    }
  }
  return false;
};
