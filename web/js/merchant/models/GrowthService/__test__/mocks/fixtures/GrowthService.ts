const ANNOUNCEMENT_ASSET_DATA_RES = [
  {
    tracking_data: {
      campaign: 'Test UI',
      campaign_description: 'Test UI',
      sub_campaign: 'May22_SSL_Razorpay_API_Update',
      sub_campaign_description: 'May22_SSL_Razorpay_API_Update',
      campaign_id: 'JOUeTGscv5XOi1',
      sub_campaign_id: 'JZMxnmXMcVU2G9',
    },
    templates: [
      {
        id: 'JZNFw5ZRxfHuKK',
        asset: 'ANNOUNCEMENT',
        name: 'SSL Certificate Update for Razorpay API',
        description: 'SSL Certificate Update for Razorpay API',
        data: {
          buttons: [
            {
              label: 'Try For Free',
              type: 'button',
              url: '/magic',
            },
            {
              id: 'announcement-details-l2',
              label: 'Read More',
              type: 'primary-inverted',
              url: '/announcements/May22_SSL_Razorpay_API_Update/',
            },
          ],
          description:
            'We\u2019re updating the SSL certificate for api.razorpay.com on 1st June 2022. To understand if this update affects you, click on the link below.',
          end_ts: 1653472117,
          icon: 'https://cdn.razorpay.com/static/assets/notifs/alert.svg',
          id: 'May22_SSL_Razorpay_API_Update',
          l2_content: {
            buttons: [
              {
                label: 'Learn more',
                type: 'button',
                url: 'https://razorpay.com/docs/whitelists/ ',
              },
            ],
            content:
              '<div class="paragraph"><b>Hi,</b><p class="paragraph">Please be informed that the TLS/SSL certificate associated with the Razorpay API will expire soon. We are scheduled to renew and import the new certificate on June 1st,2022. </p><p class="paragraph">This doesn\'t require any action on your end if you haven\'t whitelisted/pinned our certificate. But if you have done so, please make sure you import and whitelist our new certificate on or before June 1st, 2022. You can download the new certificate from the button below. </p><p class="paragraph">If you are not the right audience for this communication, please forward this to your tech/infra team.</p></div>',
          },
          start_ts: 1653463337,
          title: 'SSL Certificate Update for Razorpay API',
        },
        created_at: '1970-01-01T00:00:00Z',
        updated_at: '1970-01-01T00:00:00Z',
      },
    ],
  },
  {
    tracking_data: {
      campaign: 'vendor payment feature banner',
      campaign_description: 'vendor payment feature banner',
      sub_campaign: 'sub campaign debug',
      campaign_id: 'LJSTmnp91NZRE2',
      sub_campaign_id: 'LRmE8Roytb8bWK',
    },
    templates: [
      {
        id: 'LRmF2MWxnhDt0w',
        asset: 'ANNOUNCEMENT',
        name: 'ANNOUNCEMENT Template',
        description: 'ANNOUNCEMENT Description',
        data: {
          buttons: [
            {
              handler: [
                {
                  type: 'url',
                  url: 'https://www.razorpay.com',
                },
              ],
              id: 'default',
              label: 'Button',
              style: 'normal',
              type: 'button',
            },
          ],
          description: 'Test sub campaign details',
          end_ts: 1681556614,
          icon: 'https://betacdn.np.razorpay.in/growth/LRmE8Roytb8bWK/FINAL LOGO (1).png',
          id: 'sub_campaign_debug_MAR1523_ANNOUNCEMENT_LRmF2MWxnhDt0w',
          start_ts: 1678878214,
          title: 'Test sub campaign details',
        },
        created_at: '1970-01-01T00:00:00Z',
        updated_at: '1970-01-01T00:00:00Z',
      },
    ],
  },
];

const BANNER_ASSET_DATA_RES = [
  {
    tracking_data: {
      campaign: 'Reporting Campaigns',
      campaign_description: 'This group will contain all campaigns related to reporting team',
      sub_campaign: 'Report schedules v2',
      sub_campaign_description: 'Report schedules v2',
      campaign_id: 'MKJbUlp0gBY3MV',
      sub_campaign_id: 'MWZMIU46ch1NOc',
      tags: {
        campaign_source: 'Growth',
      },
    },
    templates: [
      {
        id: 'Me83dFbDOj9hhV',
        asset: 'BANNER',
        name: 'template-default-Variant A',
        data: {
          buttons: [
            {
              handler: [
                {
                  type: 'url',
                  url: 'https://dashboard.razorpay.com/app/reports/schedules',
                },
              ],
              id: 'default',
              label: 'Schedules',
              style: 'normal',
              type: 'button',
            },
          ],
          content: {
            description:
              'You can now effortlessly setup email schedules for your reports within a few clicks.',
            type: 'normal',
          },
          dismissible: true,
          id: 'Reports_Schedules_Campaign_AUG0123_BANNER_MKhow7WHYsf3zw',
          override_priority: true,
          text_link: {
            label: 'Know more',
            url: 'https://razorpay.com/docs/payments/dashboard/reports/#schedule-reports',
          },
          theme: 'purply',
          title: 'Schedule Reports',
        },
        created_at: '1970-01-01T00:00:00Z',
        updated_at: '1970-01-01T00:00:00Z',
      },
    ],
  },
  {
    tracking_data: {
      campaign: 'Razorpay POS',
      sub_campaign: 'Razorpay POS Campaign',
      sub_campaign_description: 'Razorpay POS Campaign',
      campaign_id: 'MAPDQWfbtUARuQ',
      sub_campaign_id: 'MFDK0KTy4DvDLC',
    },
    templates: [
      {
        id: 'McLLWGsjbEKyJO',
        asset: 'BANNER',
        name: 'template-default-Variant A',
        data: {
          buttons: [
            {
              handler: [
                {
                  type: 'url',
                  url: 'https://razorpay.com/pos/',
                },
              ],
              id: 'default',
              label: 'Know More',
              style: 'normal',
              type: 'button',
            },
          ],
          content: {
            description:
              'Transform your In-Store payments with Razorpay POS, the ultimate solution for a frictionless, seamless payment experience.',
            type: 'normal',
          },
          dismissible: true,
          id: 'Razorpay_POS_Campaign_JUL1823_BANNER_MFDLVodxcJdsZt',
          override_priority: false,
          theme: 'primary',
          title: 'Razorpay POS',
        },
        created_at: '1970-01-01T00:00:00Z',
        updated_at: '1970-01-01T00:00:00Z',
      },
    ],
  },
  {
    tracking_data: {
      campaign: 'Razorpay POS',
      sub_campaign: 'Razorpay POS Campaign',
      sub_campaign_description: 'Razorpay POS Campaign',
      campaign_id: 'MAPDQWfbtUARuq',
      sub_campaign_id: 'MFDK0KTy4DvDLc',
    },
    templates: [
      {
        id: 'McLLWGsjbEKyJo',
        asset: 'BANNER',
        name: 'template-default-Variant A',
        data: {
          buttons: [
            {
              handler: [
                {
                  type: 'url',
                  url: 'https://razorpay.com/pos/',
                },
              ],
              id: 'default',
              label: 'Know More',
              style: 'normal',
              type: 'button',
            },
          ],
          content: {
            description:
              'Transform your In-Store payments with Razorpay POS, the ultimate solution for a frictionless, seamless payment experience.',
            type: 'normal',
          },
          dismissible: true,
          id: 'Razorpay_POS_Campaign_JUL1823_BANNER_MFDLVodxcJdsZT',
          override_priority: true,
          theme: 'primary',
          title: 'Razorpay POS',
        },
        created_at: '1970-01-01T00:00:00Z',
        updated_at: '1970-01-01T00:00:00Z',
      },
    ],
  },
];

export { ANNOUNCEMENT_ASSET_DATA_RES, BANNER_ASSET_DATA_RES };
