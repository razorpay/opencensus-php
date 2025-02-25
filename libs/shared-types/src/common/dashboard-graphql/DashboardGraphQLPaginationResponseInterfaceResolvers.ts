import { DashboardGraphQLResolversParentTypes, DashboardGraphQLTypeResolveFn, DashboardGraphQLResolver, DashboardGraphQLResolversTypes, DashboardGraphQLMaybe } from './index';
export type DashboardGraphQLPaginationResponseInterfaceResolvers<
  ContextType = any,
  ParentType extends DashboardGraphQLResolversParentTypes['DashboardGraphQLPaginationResponseInterface'] = DashboardGraphQLResolversParentTypes['DashboardGraphQLPaginationResponseInterface'],
> = {
  __resolveType: DashboardGraphQLTypeResolveFn<
    | 'DashboardGraphQLBudgetsResponse'
    | 'DashboardGraphQLExpenseCategoriesResponse'
    | 'DashboardGraphQLInvoicesResponse'
    | 'DashboardGraphQLMerchantContactFundAccountsResponse'
    | 'DashboardGraphQLMerchantContactsResponse'
    | 'DashboardGraphQLMerchantStoreListResponse'
    | 'DashboardGraphQLMerchantVirtualAccountsResponse'
    | 'DashboardGraphQLPaymentLinksResponse'
    | 'DashboardGraphQLPaymentPageTransactionResponse'
    | 'DashboardGraphQLPaymentPagesResponse'
    | 'DashboardGraphQLPaymentsResponse'
    | 'DashboardGraphQLPayoutBatchesResponse'
    | 'DashboardGraphQLPayoutLinksResponse'
    | 'DashboardGraphQLPayoutsResponse'
    | 'QRCodesResponse'
    | 'DashboardGraphQLRefundsResponse'
    | 'DashboardGraphQLSalesOnboardedMerchants'
    | 'DashboardGraphQLSettlementsResponse'
    | 'DashboardGraphQLTransactionsResponse'
    | 'DashboardGraphQLVendorPaymentsResponse',
    ParentType,
    ContextType
  >;
  limit?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['PositiveInt'], ParentType, ContextType>;
  offset?: DashboardGraphQLResolver<DashboardGraphQLResolversTypes['NonNegativeInt'], ParentType, ContextType>;
  total?: DashboardGraphQLResolver<DashboardGraphQLMaybe<DashboardGraphQLResolversTypes['NonNegativeInt']>, ParentType, ContextType>;
};