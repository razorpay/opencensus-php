//TODO: Not needed remove this post initial release
export const ProductsToBeShownAlways = new Set([
  'payments_top_navigation_item',
  'banking_top_navigation_item',
  'payroll_top_navigation_item',
]);

const mockNew = {
  id: '2',
  type: 'one_navigation',
  title: '',
  actions: [],
  inputs: [],
  components: [
    {
      id: '21',
      type: 'top_navigation_item',
      title: 'Payments',
      description: 'Payments',
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
          alias: 'payments_dashboard_page',
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
    },
    {
      id: '22',
      type: 'top_navigation_item',
      title: 'Banking',
      description: 'Banking',
      actions: [],
      inputs: [],
      components: [
        {
          id: '223',
          type: 'navigate',
          title: '',
          background_img: '',
          description: '',
          actions: [
            {
              title: '',
              action: '',
              type: 'navigate',
              icon: 'right_arrow_icon',
              icon_position: 'right',
              action_params: {
                url: 'https://x.razorpay.com/',
              },
            },
          ],
          inputs: [],
          components: [],
          alias: 'banking_navigate',
          analytics: {
            enabled: true,
          },
          styles: null,
        },
      ],
      data: {
        navigation_data: {
          default_item: false,
          icon: 'RazorpayXIcon',
        },
      },
      alias: 'banking_top_navigation_item',
      analytics: {
        enabled: true,
      },
      styles: null,
    },
    {
      id: '23',
      type: 'top_navigation_item',
      title: 'Payroll',
      description: 'Payroll',
      actions: [],
      inputs: [],
      components: [
        {
          id: '231',
          type: 'growth_page',
          title: 'Fully-Automated Payroll and Compliance Software',
          background_img:
            'https://fastly.picsum.photos/id/128/800/600.jpg?hmac=7QDBgz7X_LC3wAX94h2HkRaicKh-UNqM-n4hKB5sFsA',
          description:
            'Automate your payroll software with precision - disburse salaries, file & pay taxes, like TDS, PF, PT & ESIC, and more from a single dashboard on your payroll software',
          actions: [
            {
              title: 'Get started with payroll now',
              action: 'navigate',
              type: 'button',
              icon: 'arrow_right',
              icon_position: 'right',
              action_params: {
                url: 'https://payroll.razorpay.com/',
              },
              properties: {
                variant: 'primary',
              },
            },
            {
              title: 'Know more',
              action: 'navigate',
              type: 'button',
              icon: '',
              icon_position: '',
              action_params: {
                url: 'https://payroll.razorpay.com/',
              },
              properties: {
                variant: 'secondary',
              },
            },
          ],
          inputs: [],
          components: [],
          alias: 'payroll_growth_page',
          analytics: {
            enabled: true,
          },
          styles: null,
        },
      ],
      data: {
        navigation_data: {
          default_item: true,
          icon: 'RazorpayxPayrollIcon',
        },
      },
      alias: 'payroll_top_navigation_item',
      analytics: {
        enabled: true,
      },
      styles: null,
    },
    {
      id: '25',
      type: 'top_navigation_item',
      title: 'Partnership',
      description: 'Partnership',
      actions: [],
      inputs: [],
      components: [
        {
          id: '262',
          type: 'access_denied_page',
          background_img:
            'https://fastly.picsum.photos/id/146/800/600.jpg?hmac=wHoLAgUr9LhOP8wlT8txq4QqHlpyau0ZP-qmRHfzdgM',
          title: 'You do not have access to Partnership Dashboard',
          description:
            "Your organization uses Partnership, please reach out to your admin if you need access to Partnership's Dashboard",
          actions: [],
          inputs: [],
          components: [],
          alias: 'partnership_access_denied_page',
          analytics: {
            enabled: true,
          },
          styles: null,
        },
      ],
      data: {
        navigation_data: {
          default_item: true,
          icon: 'UsersIcon',
        },
      },
      alias: 'partners_top_navigation_item',
      analytics: {
        enabled: true,
      },
      styles: null,
    },
    {
      id: '27',
      type: 'more_navigation_item',
      title: 'More',
      actions: [],
      inputs: [],
      components: [
        {
          id: '271',
          type: '',
          title: 'Bill Me',
          description: '',
          actions: [],
          inputs: [],
          components: [
            {
              id: '2711',
              type: 'growth_page',
              title:
                'RazorpayBillme redefines how you send bills and conduct your post-purchase interactions with your customers',
              description:
                'RazorpayBillme isn’t just your run-of-the-mill digital billing platform – it is a game-changer for businesses of all shapes and sizes.  RazorpayBillme offers a user-friendly interface along with a robust set of digital billing tools, a data platform, a retention suite, and an omnichannel CRM.',
              background_img:
                'https://fastly.picsum.photos/id/950/800/600.jpg?hmac=VCWz8OFSt4IipU4c8yejk0ytSYDvI9IQzZLXmFCFTSg',
              actions: [
                {
                  title: 'Get started with Bill me now',
                  action: 'navigate',
                  type: 'button',
                  icon: 'arrow_right',
                  icon_position: 'right',
                  action_params: {
                    url: 'https://billme.razorpay.com/login',
                  },
                  properties: {
                    variant: 'primary',
                  },
                },
                {
                  title: 'Know more',
                  action: 'navigate',
                  type: 'button',
                  icon: '',
                  icon_position: '',
                  action_params: {
                    url: 'https://razorpay.com/blog/everything-you-need-to-know-about-razorpaybillme/',
                  },
                  properties: {
                    variant: 'secondary',
                  },
                },
              ],
              inputs: [],
              components: [],
              alias: 'billme_growth_page',
              analytics: {
                enabled: true,
              },
              styles: null,
            },
          ],
          data: {
            navigation_data: {
              default_item: true,
              icon: 'BillIcon',
            },
          },
          alias: 'billme_top_navigation_item',
          analytics: {
            enabled: true,
          },
          styles: null,
        },
      ],
      analytics: null,
      styles: null,
    },
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

export const getProducts = () => {
  return new Promise((resolve, reject) => {
    setTimeout(() => {
      //   if (Math.random() <= 0.2) { //to mimic error state
      //     reject(new Error('Error in fetching the products data'));
      //   }

      resolve(mockNew);
    }, 2000);
  });
};
