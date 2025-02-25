export type DashboardGraphQLErrorResponse = {
    response: { errors: Array<{ message?: string }> | Array<string> };
  };