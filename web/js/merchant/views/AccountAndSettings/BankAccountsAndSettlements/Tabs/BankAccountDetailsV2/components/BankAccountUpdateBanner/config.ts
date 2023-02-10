import { BannerType } from 'merchant/views/AccountAndSettings/BankAccountsAndSettlements/Tabs/BankAccountDetailsV2/components/Banner/config';

export interface HomepageBannersInfo {
  title: string;
  theme: 'success' | 'danger' | 'warning';
  description: string;
  knowMoreLink: string;
}

const commonProp = {
  knowMoreLink: '/bank-accounts-settlements/bank-account-details',
};

export const getHomePageBanners = ({ type }: { type: BannerType }): HomepageBannersInfo | null => {
  switch (type) {
    case BannerType.SUCCESS:
      return {
        title: 'Updated',
        theme: 'success',
        description: 'Your bank account change request was successful',
        ...commonProp,
      };
    case BannerType.ACTIVE_SETTLEMENT_REJECTED:
    case BannerType.INACTIVE_SETTLEMENT_REJECTED:
      return {
        title: 'Rejected',
        theme: 'danger',
        description: 'Your bank account change request is rejected',
        ...commonProp,
      };
    case BannerType.INACTIVE_SETTLEMENT_NC:
    case BannerType.ACTIVE_SETTLEMENT_NC:
      return {
        title: 'Needs Clarification',
        theme: 'warning',
        description: 'We need a few more details for your bank account verification',
        ...commonProp,
      };
    default:
      return null;
  }
};
