export const homeFallbackData = {
  id: '20',
  type: 'top_navigation_item',
  title: 'Razorpay Home',
  handler_id: 'home_one_navigation',
  components: [
    {
      id: '201',
      type: 'navigate',
      handler_id: 'home_navigate',
      inherit_rule: true,
      actions: [
        {
          title: '',
          action: '',
          type: 'navigate',
          icon: '',
          icon_position: '',
          action_params: {
            path: '/home',
          },
        },
      ],
      alias: 'home_navigate',
      analytics: {
        enabled: true,
      },
      metadata: {
        shared_id: 'home_navigate',
      },
    },
  ],
  data: {
    navigation_data: {
      default_item: false,
      icon: 'HomeIcon',
    },
  },
  alias: 'home_top_navigation_item',
  analytics: {
    enabled: true,
  },
  metadata: {
    shared_id: 'home_top_navigation_item',
    splitz: ['PzygPjXxh0630V'],
  },
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
      alias: 'payments_navigate',
      analytics: {
        enabled: true,
      },
      metadata: {
        splitz: [],
        transformations: {},
        tags: [],
        product_id: '',
        shared_id: 'payments_navigate',
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
  metadata: {
    splitz: [],
    transformations: {},
    tags: [],
    product_id: '',
    shared_id: 'payments_top_navigation_item',
  },
  styles: null,
};

export const partnerFallbackData = {
  id: '24',
  type: 'top_navigation_item',
  title: 'Partners',
  description: "India's most comprehensive partner program for payments and beyond",
  actions: [],
  inputs: [],
  components: [
    {
      id: '242',
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
      alias: 'partners_navigate',
      analytics: {
        enabled: true,
      },
      metadata: {
        splitz: [],
        transformations: {},
        tags: [],
        product_id: '',
        shared_id: 'partners_navigate',
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
  metadata: {
    splitz: [],
    transformations: {},
    tags: [],
    product_id: '',
    shared_id: 'partners_top_navigation_item',
  },
  styles: null,
};
