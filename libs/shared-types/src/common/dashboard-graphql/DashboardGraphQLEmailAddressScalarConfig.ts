import { DashboardGraphQLResolversTypes, GraphQLScalarTypeConfig } from './index';
export interface DashboardGraphQLEmailAddressScalarConfig
  extends GraphQLScalarTypeConfig<DashboardGraphQLResolversTypes['EmailAddress'], any> {
  name: 'EmailAddress';
}