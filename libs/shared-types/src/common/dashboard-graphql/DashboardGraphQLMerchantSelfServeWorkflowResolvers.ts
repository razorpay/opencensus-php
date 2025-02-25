import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLMaybe, DashboardGraphQLResolversTypes, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantSelfServeWorkflowResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantSelfServeWorkflow'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantSelfServeWorkflow'],
> = {
  bankAccountId?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['ID']>, ParentType, ContextType>;
  createdAt?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DateTime']>, ParentType, ContextType>;
  customerActions?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<Array<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantGstinUpdateCustomerActionEnum']>>>,
    ParentType,
    ContextType
  >;
  isRequestUnderBvsValidation?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  isWorkflowExits?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  needsClarificationMessage?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  permission?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantSelfServeGstinPermissionEnum']>,
    ParentType,
    ContextType
  >;
  rejectedAt?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DateTime']>, ParentType, ContextType>;
  rejectionReason?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['String']>, ParentType, ContextType>;
  workflowStatus?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantGstinWorkflowStatusEnum']>,
    ParentType,
    ContextType
  >;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};