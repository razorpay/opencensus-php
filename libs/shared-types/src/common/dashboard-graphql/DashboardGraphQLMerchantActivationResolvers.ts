import { DashboardGraphQLResolversParentTypes, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe, DashboardGraphQLIsTypeOfResolverFn } from './index';
export type DashboardGraphQLMerchantActivationResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantActivation'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLMerchantActivation'],
> = {
  activationStatusChangeLogs?: DashboardGraphQLResolver<
    Array<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantActivationStatusEnum']>,
    ParentType,
    ContextType
  >;
  allowedPosStatus?: DashboardGraphQLResolver<
    Array<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantPosActivationStatusEnum']>>,
    ParentType,
    ContextType
  >;
  allowedStatus?: DashboardGraphQLResolver<
    Array<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantActivationStatusEnum']>>,
    ParentType,
    ContextType
  >;
  canSubmitForm?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  dedupe?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantActivationDedupe']>, ParentType, ContextType>;
  documentsSubmittedAt?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DateTime']>, ParentType, ContextType>;
  feeBasedGating?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantFeeBasedGating']>,
    ParentType,
    ContextType
  >;
  flow?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantActivationFlow']>, ParentType, ContextType>;
  isActivated?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  isAutoKycDone?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  isDedupe?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  isFormLocked?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['Boolean']>, ParentType, ContextType>;
  isFormSubmitted?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  isHardLimitReached?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  isInternational?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  isPgosMerchant?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['Boolean']>, ParentType, ContextType>;
  isTransacted?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  merchantEscalations?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantEscalations'], ParentType, ContextType>;
  milestone?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantActivationMilestoneEnum']>,
    ParentType,
    ContextType
  >;
  paymentsActivatedAt?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DateTime']>, ParentType, ContextType>;
  posActivationFlow?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantActivationFlowEnum']>,
    ParentType,
    ContextType
  >;
  posActivationStatus?: DashboardGraphQLResolver<
    DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantPosActivationStatusEnum']>,
    ParentType,
    ContextType
  >;
  posActivationStatusChangeLogs?: DashboardGraphQLResolver<
    Array<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantPosActivationStatusEnum']>>,
    ParentType,
    ContextType
  >;
  posDetailsSubmitted?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['Boolean'], ParentType, ContextType>;
  progressPercent?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  status?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['DashboardGraphQLMerchantActivationStatusEnum']>, ParentType, ContextType>;
  __isTypeOf?: DashboardGraphQLIsTypeOfResolverFn<ParentType, ContextType>;
};