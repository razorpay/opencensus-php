import { I18ContextStateType } from 'common/i18/types';
import { User } from 'common/typings';
import { trackProductTabClick } from 'merchant/views/PartnerDashboard/ClientAccounts/analytics';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { PartnerDashboardExperiments } from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments';

export type TabData = {
  isActive: () => boolean;
  productType: string;
  title: string;
  url: string;
  hidden: boolean;
  onTabClick: () => void;
};

const tabsConfig: Array<Pick<TabData, 'url' | 'title' | 'productType'>> = [
  {
    url: '/partners/submerchants',
    title: 'Payments',
    productType: PRODUCT_TYPE.PG,
  },
  {
    url: '/partners/submerchants/pos',
    title: 'POS',
    productType: PRODUCT_TYPE.POS,
  },
  {
    url: '/partners/submerchants/capital',
    title: 'Line Of Credit',
    productType: PRODUCT_TYPE.CAPITAL,
  },
  {
    url: '/partners/submerchants/x',
    title: 'RazorpayX',
    productType: PRODUCT_TYPE.X,
  },
];
type getTabsDataArgs = {
  user: User;
  i18: I18ContextStateType;
  experiments: PartnerDashboardExperiments;
  productType: string;
};

type getTabsDataValue = {
  productTypeVisibilityMap: Record<string, boolean>;
  tabsData: Array<TabData>;
};
export const getTabsData = ({
  user,
  i18,
  experiments,
  productType,
}: getTabsDataArgs): getTabsDataValue => {
  const partnerId = user.id as string;
  const { isPartnershipsForPosEnabled } = experiments;

  const shouldShowX =
    !isPartnershipsForPosEnabled &&
    user.isPartner() &&
    !user.isPartner('pure_platform') &&
    !i18.isConfigTagEnabled('partnership.razorpay_x_affiliate_account');

  const productTypeVisibilityMap = {
    [PRODUCT_TYPE.PG]: true,
    [PRODUCT_TYPE.POS]: isPartnershipsForPosEnabled,
    [PRODUCT_TYPE.CAPITAL]: user.isPartnershipForCapitalEnabled,
    [PRODUCT_TYPE.X]: shouldShowX,
  };

  const tabsData = tabsConfig.map((tab) => ({
    ...tab,
    isActive: () => productType === tab.productType,
    hidden: !productTypeVisibilityMap[tab.productType],
    onTabClick: () => trackProductTabClick(tab.productType, partnerId),
  }));
  return { productTypeVisibilityMap, tabsData };
};

type getIsInviteFlowEnabledValue = {
  isInviteFlowEnabled: boolean;
  isResellerInviteFlowEnabled: boolean;
  isPlatformPartnerWithPGInviteFlow: boolean;
};
export const getIsInviteFlowEnabled = (
  productType: string,
  experiments: PartnerDashboardExperiments,
): getIsInviteFlowEnabledValue => {
  const { isPartnershipsInviteFlowEnabled, isPlatformPartnerInviteFlowEnabled } = experiments;
  const isPlatformPartnerWithPGInviteFlow =
    isPlatformPartnerInviteFlowEnabled && productType === PRODUCT_TYPE.PG;
  const isResellerInviteFlowEnabled =
    (isPartnershipsInviteFlowEnabled && productType === PRODUCT_TYPE.PG) ||
    productType === PRODUCT_TYPE.POS;
  return {
    isInviteFlowEnabled: isPlatformPartnerWithPGInviteFlow || isResellerInviteFlowEnabled,
    isResellerInviteFlowEnabled,
    isPlatformPartnerWithPGInviteFlow,
  };
};
