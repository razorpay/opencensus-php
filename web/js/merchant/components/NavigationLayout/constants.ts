import {
  AcceptPaymentsIcon,
  MenuDotsIcon,
  PaymentLinksIcon,
  PaymentPagesIcon,
  TransactionsIcon,
} from '@razorpay/blade/components';

export const ONE_NAV_MOBILE_PATH = '/home';

export const ANALYTICS_ONENAV_EXPERIMENT = 'oneNavV1';

export const HOTJAR_TRIGGERS = {
  CONNECTED_NAV_FIRST: 'conn-nav-first',
  CONNECTED_NAV_REPEAT: 'conn-nav-repeat',
};

export const paymentsFallbackData = {
  id: '21',
  type: 'top_navigation_item',
  title: 'Payments',
  description: 'Accept payments for your business seamlessly',
  actions: [],
  inputs: [],
  components: [
    {
      id: '213',
      type: 'navigate',
      title: '',
      background_img: '',
      description: '',
      actions: [
        {
          title: '',
          action: '',
          type: 'navigate',
          icon: '',
          icon_position: '',
          action_params: {
            path: '/dashboard',
          },
        },
      ],
      inputs: [],
      components: [],
      alias: 'payments_navigate_page',
      analytics: {
        enabled: true,
      },
      styles: null,
    },
  ],
  data: {
    navigation_data: {
      default_item: true,
      icon: 'AcceptPaymentsIcon',
    },
  },
  alias: 'payments_top_navigation_item',
  analytics: {
    enabled: true,
  },
  styles: null,
};

export const partnersFallbackData = {
  id: '24',
  type: 'top_navigation_item',
  title: 'Partners',
  description: "India's most comprehensive partner program for payments and beyond",
  actions: [],
  inputs: [],
  components: [
    {
      id: '243',
      type: 'navigate',
      title: '',
      background_img: '',
      description: '',
      actions: [
        {
          title: '',
          action: '',
          type: 'navigate',
          icon: '',
          icon_position: '',
          action_params: {
            path: '/partners',
          },
        },
      ],
      inputs: [],
      components: [],
      alias: 'partnership_navigate',
      analytics: {
        enabled: true,
      },
      styles: null,
    },
  ],
  data: {
    navigation_data: {
      default_item: false,
      icon: 'UsersIcon',
    },
  },
  alias: 'partners_top_navigation_item',
  analytics: {
    enabled: true,
  },
  styles: null,
};

export const defaultConnectedProductsData = {
  id: '2',
  type: 'one_navigation',
  title: '',
  actions: [],
  inputs: [],
  components: [
    paymentsFallbackData, // Always include Payments fallback
  ],
  data: {
    navigation_data: {
      brand_logo: 'https://upload.wikimedia.org/wikipedia/commons/7/77/Razorpay_logo.png',
    },
  },
  alias: 'one_navigation',
  analytics: {
    enabled: true,
  },
  styles: null,
};

export const BOTTOM_NAV_ITEMS = [
  {
    title: 'Payments',
    icon: AcceptPaymentsIcon,
    href: '/dashboard',
  },
  {
    title: 'Transactions',
    icon: TransactionsIcon,
    href: '/payments',
  },
  {
    title: 'Links',
    icon: PaymentLinksIcon,
    href: '/paymentlinks',
  },
  {
    title: 'Pages',
    icon: PaymentPagesIcon,
    href: '/paymentpages',
  },
  {
    title: 'More',
    icon: MenuDotsIcon,
  },
];
