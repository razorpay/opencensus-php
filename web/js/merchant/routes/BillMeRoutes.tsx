import React, { FC } from 'react';
import { RouteGuard } from 'merchant/components/ShowWhen';
import { PaymentsDashboardUser } from '@libs/shared-types/payments';
import { getMode } from '@federated/apps/shell/commonStore';

export type RouteComponentMap = {
  HandleIndex: FC<any>;
  DigitalBills: FC<any>;
  MerchantReports: FC<any>;
  BillMeSettings: FC<any>;
  StoreSettings: FC<any>;
  AccountAndSettingsHome: FC<any>;
};

interface GetBillMeRoutesProps {
  user: PaymentsDashboardUser;
  isBillMeMerchant: boolean;
  isConnectedNavigation: boolean;
  mode: string;
  components: RouteComponentMap;
}

export const getBillMeRoutes = ({
  components,
}: GetBillMeRoutesProps) => {
  const {
    MerchantReports,
    BillMeSettings,
    StoreSettings,
    AccountAndSettingsHome,
  } = components;
  const mode = getMode();

  const BillMeRoutes = [
    {
      path: 'reports/*',
      element:
        <RouteGuard
          additionalCondition={(user) =>
            (user.isAllowedView('reports') || user.isCareHealthOwner) &&
            user.hideForNIASupportRole
          }
        >
          <MerchantReports />
        </RouteGuard>
    },
    {
      path: 'billme-settings/*',
      element:
        <RouteGuard
          additionalCondition={() =>
            mode === 'live'
          }
        >
          <BillMeSettings />
        </RouteGuard>
    },
    {
      path: 'store-settings/*',
      element:
        <RouteGuard
          additionalCondition={() =>
            mode === 'live'
          }
        >
          <StoreSettings />
        </RouteGuard>
    },
    {
      path: 'account-settings',
        element: 
        <RouteGuard
          additionalCondition={(user) =>
            user.isAccountAndSettingsRevampEnabled &&
            user.isAllowedMultiple(
              'webhooks applications configuration api_keys profile credits add_funds team referrals'
            )
          }
        >
          <AccountAndSettingsHome />
        </RouteGuard>
    },
  ];

  return BillMeRoutes;
};
