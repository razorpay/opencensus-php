import {
  UsersIcon,
  UserPlusIcon,
  FileTextIcon,
  SidebarIcon,
  ReportsIcon,
  SettingsIcon,
  AppStoreIcon,
  UserIcon,
  DiscIcon,
  BankAccountVerificationIcon,
  CoinsIcon,
} from '@razorpay/blade/components';

import { checkIfPosSalesAgent } from 'common/utils/posAgent';
import {
  isPartnershipsForPosEnabled,
  getIsPosKycEnabled,
  isPartnerPlaybookEnabled,
} from 'merchant/views/PartnerDashboard/hooks/usePartnerDashboardExperiments.ts';

const PARTNER_SIDE_NAV_ITEMS = [
  {
    label: 'Home',
    title: 'Home',
    href: '/partners',
    icon: 'i i-chart text-info',
    bladeIcon: DiscIcon,
    to: '/partners',
    end: true,
    type: 'partner',
    additionalCondition: (user) => user.isPartnershipFUX,
  },
  {
    label: 'Affiliate Accounts',
    title: 'Affiliate Accounts',
    href: '/partners/submerchants',
    icon: 'i i-account-balance text-success',
    bladeIcon: BankAccountVerificationIcon,
    to: '/partners/submerchants',
    end: true,
    additionalCondition: (user) => user.isAllowedView('submerchants'),
  },
  {
    label: 'Manage Team',
    title: 'Manage Team',
    href: '/partners/manage-team',
    icon: 'i i-settings text-warning',
    bladeIcon: UsersIcon,
    to: '/partners/manage-team',
    isNew: true,
    end: true,
    additionalCondition: (user, { abExperiments }) =>
      (isPartnershipsForPosEnabled({ user, abExperiments }) ||
        !!checkIfPosSalesAgent({ user, abExperiments })?.isOwner) &&
      user.isAllowedTeamManagement,
  },
  {
    label: 'Manage Team eKYC',
    title: 'Manage Team eKYC',
    href: '/partners/pos-ekyc-team',
    icon: 'i i-settings text-warning',
    bladeIcon: UserPlusIcon,
    to: '/partners/pos-ekyc-team',
    isNew: true,
    end: true,
    additionalCondition: (user) => getIsPosKycEnabled({ user }),
  },
  {
    label: 'Partner Playbook',
    title: 'Partner Playbook',
    href: '/partners/playbook',
    icon: 'i i-partner-playbook text-notice',
    bladeIcon: FileTextIcon,
    to: '/partners/playbook',
    isNew: true,
    end: true,
    additionalCondition: (user, { abExperiments }) =>
      isPartnerPlaybookEnabled({ user, abExperiments }),
  },
  {
    label: 'Earnings',
    title: 'Earnings',
    href: '/partners/earnings/daily',
    icon: 'i i-earnings text-primary',
    bladeIcon: CoinsIcon,
    to: '/partners/earnings/daily',
    end: true,
    additionalCondition: (user) => user.isAllowedView('earnings') && user.isHavingPartnerConfigs,
  },
  {
    label: 'Subventions',
    title: 'Subventions',
    href: '/partners/subventions/daily',
    icon: 'i i-earnings text-warning',
    bladeIcon: SidebarIcon,
    to: '/partners/subventions/daily',
    end: true,
    additionalCondition: (user) => user.isAllowedView('earnings') && user.isHavingSubventionConfigs,
  },
  {
    label: 'Settings',
    title: 'Settings',
    href: '/partners/settings',
    icon: 'i i-settings text-warning',
    bladeIcon: SettingsIcon,
    to: '/partners/settings',
    end: true,
    additionalCondition: (user) =>
      user.isAllowedView('partner_settings') && user.isPartner('aggregator', 'fully_managed'),
  },
  {
    label: 'Applications',
    title: 'Applications',
    href: '/partners/applications',
    icon: 'i i-settings text-warning',
    bladeIcon: AppStoreIcon,
    to: '/partners/applications',
    end: true,
    additionalCondition: (user) =>
      user.isAllowedView('partner_applications') && user.isPartner('pure_platform'),
  },
  {
    label: 'Reports',
    title: 'Reports',
    href: '/partners/reports',
    icon: 'i i-books text-danger',
    bladeIcon: ReportsIcon,
    to: '/partners/reports',
    isPending: false,
    additionalCondition: (user) =>
      user.isAllowedView('partner_reports') &&
      (!user.isPartner('reseller') || user.isHavingPartnerConfigs),
  },
];

const PARTNER_SIDE_NAV_FOOTER_ITEMS = [
  {
    label: 'Partner Accounts & Settings',
    title: 'Partner Accounts & Settings',
    href: '/partners/accounts-settings',
    bladeIcon: SettingsIcon,
    icon: 'i i-settings text-warning',
    to: '/partners/accounts-settings',
    additionalCondition: () => true,
    end: true,
  },
];

const PARTNER_SIDE_NAV_POS_SALES_ITEM = [
  {
    label: 'POS Sales Dashboard',
    title: 'POS Sales Dashboard',
    bladeIcon: UserIcon,
    href: '/pos-sales',
    icon: 'i i-settings text-warning',
    to: '/pos-sales',
    additionalCondition: (user, { abExperiments }) => {
      const { isPosSalesAgent } = checkIfPosSalesAgent({
        user,
        abExperiments,
      });
      return isPosSalesAgent;
    },
    end: true,
  },
];
export const PARTNER_SIDE_NAV_SECTION = {
  section_name: '',
  section_id: 'partner_side_nav_section',
  product_options: PARTNER_SIDE_NAV_ITEMS,
};

export const PARTNER_SIDENAV_POS_SALES_SECTION = {
  section_name: '',
  section_id: 'partner_side_nav_section',
  product_options: PARTNER_SIDE_NAV_POS_SALES_ITEM,
};

export const PARTNER_SIDE_NAV_FOOTER_SECTION = {
  section_name: '',
  section_id: 'partner_side_nav_footer_section',
  product_options: PARTNER_SIDE_NAV_FOOTER_ITEMS,
};
