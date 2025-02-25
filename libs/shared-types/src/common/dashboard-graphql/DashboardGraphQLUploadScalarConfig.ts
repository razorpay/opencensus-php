import { DashboardGraphQLResolversTypes, GraphQLScalarTypeConfig } from './index';
export interface DashboardGraphQLUploadScalarConfig extends GraphQLScalarTypeConfig<DashboardGraphQLResolversTypes['Upload'], any> {
  name: 'Upload';
}