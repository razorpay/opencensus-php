export const API_RESPONSE = {
  data: {
    from: 1675967400,
    to: 1683789356,
    buckets: 0,
    aggregate: 'daily',
    metrics: {
      total_sales: {
        title: 'Total Sales',
        timestamps: [1682899200, 1682985600, 1683072000, 1683158400, 1683244800],
        values: [11004000, 8753400, 7805500, 8848500, 9825900],
        unit: 'paise',
        updated_at: 1683244800,
      },
      total_orders_placed: {
        title: 'Total Orders Placed',
        timestamps: [1682899200, 1682985600, 1683072000, 1683158400, 1683244800],
        values: [129, 103, 100, 117, 120],
        unit: 'count',
        updated_at: 1683244800,
      },
      average_order_value: {
        title: 'Average Order Value',
        timestamps: [1682899200, 1682985600, 1683072000, 1683158400, 1683244800],
        values: [85302, 84984, 78055, 75628, 81882],
        unit: 'paise',
        updated_at: 1683244800,
      },
      prepay_vs_cod: {
        title: 'Prepaid vs COD orders',
        timestamps: [1682899200, 1682985600, 1683072000, 1683158400, 1683244800],
        total_sales: {
          values: [
            {
              label: 'COD',
              values: [0, 0, 0, 509400, 594300],
            },
            {
              label: 'Prepaid',
              values: [11004000, 8753400, 7805500, 8339100, 9231600],
            },
          ],
          unit: 'paise',
          updated_at: 1683244800,
        },
        total_orders_placed: {
          values: [
            {
              label: 'COD',
              values: [0, 0, 0, 6, 7],
            },
            {
              label: 'Prepaid',
              values: [129, 103, 100, 111, 113],
            },
          ],
          unit: 'count',
          updated_at: 1683244800,
        },
      },
      traffic_utm: {
        title: 'Traffic by UTM parameters',
        utm_source: [
          {
            label: 'Whatsapp',
            sales: 9620200,
            order_count: 98,
            utm_medium: [
              {
                label: 'Whatsapp',
                sales: 9590400,
                order_count: 96,
                utm_campaign: [
                  {
                    label: 'MAY01',
                    sales: 9590400,
                    order_count: 96,
                  },
                ],
              },
              {
                label: 'WHATSAPP',
                sales: 29800,
                order_count: 2,
                utm_campaign: [
                  {
                    label: 'apr25',
                    sales: 29800,
                    order_count: 2,
                  },
                ],
              },
            ],
          },
          {
            label: 'direct',
            sales: 1287300,
            order_count: 15,
            utm_medium: [
              {
                label: 'others',
                sales: 1287300,
                order_count: 15,
                utm_campaign: [
                  {
                    label: 'others',
                    sales: 1287300,
                    order_count: 15,
                  },
                ],
              },
            ],
          },
          {
            label: 'WHATSAPP',
            sales: 18295200,
            order_count: 248,
            utm_medium: [
              {
                label: 'WHATSAPP',
                sales: 16696800,
                order_count: 232,
                utm_campaign: [
                  {
                    label: 'APR24',
                    sales: 199600,
                    order_count: 4,
                  },
                  {
                    label: 'MAY08',
                    sales: 1197600,
                    order_count: 24,
                  },
                  {
                    label: 'apr24',
                    sales: 199600,
                    order_count: 4,
                  },
                  {
                    label: 'APR02FREEPB',
                    sales: 39600,
                    order_count: 4,
                  },
                  {
                    label: 'May04',
                    sales: 1398400,
                    order_count: 16,
                  },
                  {
                    label: 'APR28',
                    sales: 249500,
                    order_count: 5,
                  },
                  {
                    label: 'Apr30',
                    sales: 2095800,
                    order_count: 42,
                  },
                  {
                    label: 'MAY02',
                    sales: 8230800,
                    order_count: 92,
                  },
                  {
                    label: 'APR17',
                    sales: 199600,
                    order_count: 4,
                  },
                  {
                    label: 'APR12',
                    sales: 349300,
                    order_count: 7,
                  },
                  {
                    label: 'MAY06',
                    sales: 2037600,
                    order_count: 24,
                  },
                  {
                    label: 'APR13',
                    sales: 399600,
                    order_count: 4,
                  },
                  {
                    label: 'APR16',
                    sales: 99800,
                    order_count: 2,
                  },
                ],
              },
              {
                label: 'whatsapp',
                sales: 1598400,
                order_count: 16,
                utm_campaign: [
                  {
                    label: 'may 05',
                    sales: 1598400,
                    order_count: 16,
                  },
                ],
              },
            ],
          },
          {
            label: 'whatsapp',
            sales: 4054000,
            order_count: 48,
            utm_medium: [
              {
                label: 'whatsapp',
                sales: 538800,
                order_count: 12,
                utm_campaign: [
                  {
                    label: 'Apr27',
                    sales: 199600,
                    order_count: 4,
                  },
                  {
                    label: 'apr22',
                    sales: 299400,
                    order_count: 6,
                  },
                  {
                    label: 'apr19',
                    sales: 39800,
                    order_count: 2,
                  },
                ],
              },
              {
                label: 'WHATSAPP',
                sales: 3515200,
                order_count: 36,
                utm_campaign: [
                  {
                    label: 'APR27',
                    sales: 318400,
                    order_count: 4,
                  },
                  {
                    label: 'MAY03',
                    sales: 3196800,
                    order_count: 32,
                  },
                ],
              },
            ],
          },
          {
            label: 'GPAY',
            sales: 69300,
            order_count: 7,
            utm_medium: [
              {
                label: 'SC',
                sales: 69300,
                order_count: 7,
                utm_campaign: [
                  {
                    label: 'APPB99',
                    sales: 69300,
                    order_count: 7,
                  },
                ],
              },
            ],
          },
          {
            label: 'PAYTM',
            sales: 339600,
            order_count: 4,
            utm_medium: [
              {
                label: '650OFF',
                sales: 339600,
                order_count: 4,
                utm_campaign: [
                  {
                    label: 'PACKOF5849',
                    sales: 339600,
                    order_count: 4,
                  },
                ],
              },
            ],
          },
          {
            label: 'Gpay',
            sales: 8161500,
            order_count: 101,
            utm_medium: [
              {
                label: 'SC',
                sales: 6208800,
                order_count: 78,
                utm_campaign: [
                  {
                    label: 'PO4PB',
                    sales: 6208800,
                    order_count: 78,
                  },
                ],
              },
              {
                label: '650OFF',
                sales: 1952700,
                order_count: 23,
                utm_campaign: [
                  {
                    label: 'ASS',
                    sales: 1952700,
                    order_count: 23,
                  },
                ],
              },
            ],
          },
          {
            label: 'WHATSAPPP',
            sales: 1438400,
            order_count: 16,
            utm_medium: [
              {
                label: 'WHATSAPP',
                sales: 1438400,
                order_count: 16,
                utm_campaign: [
                  {
                    label: 'APR20',
                    sales: 1438400,
                    order_count: 16,
                  },
                ],
              },
            ],
          },
          {
            label: 'Whatsapp ',
            sales: 2971800,
            order_count: 32,
            utm_medium: [
              {
                label: 'WHATSAPP',
                sales: 2971800,
                order_count: 32,
                utm_campaign: [
                  {
                    label: 'MAR05',
                    sales: 2697300,
                    order_count: 27,
                  },
                  {
                    label: 'MAY06',
                    sales: 274500,
                    order_count: 5,
                  },
                ],
              },
            ],
          },
        ],
        unit: 'paise',
        updated_at: 1683309996,
      },
      top_selling_products: {
        title: 'Top Selling Product',
        values: [
          {
            label:
              'W (MA01) Family Pack Of 5 Premium Dry Fruits I Almonds, Raisins, Pistachios, Whole Cashew Nuts, Dates I 750g + SUGARCANE JAGGERY',
            qty: 103,
            gmv: 16974400,
          },
          {
            label: '(A - GP) Pack of 4 Peanut Butters (800 g)',
            qty: 82,
            gmv: 13087200,
          },
          {
            label: 'W (M02) Family Pack Of 5 Premium Dry Fruits 750G',
            qty: 64,
            gmv: 9593600,
          },
        ],
        updated_at: 1683244800,
      },
    },
    updated_at: 1683244800,
  },
};
