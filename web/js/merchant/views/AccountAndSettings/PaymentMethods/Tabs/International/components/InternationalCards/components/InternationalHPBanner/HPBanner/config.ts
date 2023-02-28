import { BannerType } from 'merchant/views/AccountAndSettings/PaymentMethods/typings';

export interface HomepageBannersInfo {
  title: string;
  theme: 'success' | 'danger' | 'warning';
  description: string;
  knowMoreLink: string;
}

const commonProp = {
  knowMoreLink: '/payment-methods/international-payments',
};

export const getHomePageBanners = ({ type }: { type: BannerType }): HomepageBannersInfo | null => {
  switch (type) {
    case BannerType.APPROVED:
      return {
        title: 'Activated',
        theme: 'success',
        description: 'Your request to activate international card payments was successful',
        ...commonProp,
      };
    case BannerType.NEEDS_CLARIFICATION:
      return {
        title: 'Needs Clarification',
        theme: 'warning',
        description: 'We need a few more details for your international cards payment request',
        ...commonProp,
      };
    case BannerType.REJECTED:
      return {
        title: 'Rejected',
        theme: 'danger',
        description: 'Your request to activate international card payments is rejected',
        ...commonProp,
      };
    default:
      return null;
  }
};
