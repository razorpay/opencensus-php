export const ORDER_ANALYTICS_WITH_ALL_3_CONVERSION_RATES = {
  isFetching: false,
  analyticsData: {
    metrics: {
      total_sales: {
        title: 'Total Sales',
        timestamps: [
          1709317800, 1709321400, 1709325000, 1709328600, 1709332200, 1709335800, 1709339400,
          1709343000, 1709346600, 1709350200, 1709353800, 1709357400, 1709361000, 1709364600,
          1709368200, 1709371800, 1709375400, 1709379000, 1709382600, 1709386200, 1709389800,
          1709393400, 1709397000, 1709400600, 1709404200, 1709407800, 1709411400, 1709415000,
          1709418600, 1709422200, 1709425800, 1709429400, 1709433000, 1709436600, 1709440200,
          1709443800, 1709447400, 1709451000, 1709454600, 1709458200, 1709461800, 1709465400,
          1709469000, 1709472600, 1709476200, 1709479800, 1709483400, 1709487000,
        ],
        values: [
          955980, 323480, 41140, 0, 166500, 251345, 199490, 402700, 462330, 2234120, 1464850,
          1124185, 1161055, 1942335, 1161285, 1731365, 1711580, 1192800, 798480, 739230, 736130,
          5626935, 1888135, 1073585, 1242820, 1042405, 399540, 314235, 51570, 75420, 0, 1103310,
          633375, 877930, 2293150, 862775, 684060, 2020590, 1042990, 1445480, 734345, 741690,
          1232120, 665780, 923730, 1304995, 1549830, 1098400,
        ],
        unit: 'paise',
        updated_at: 1709487000,
      },
      total_orders_placed: {
        title: 'Total Orders Placed',
        timestamps: [
          1709317800, 1709321400, 1709325000, 1709328600, 1709332200, 1709335800, 1709339400,
          1709343000, 1709346600, 1709350200, 1709353800, 1709357400, 1709361000, 1709364600,
          1709368200, 1709371800, 1709375400, 1709379000, 1709382600, 1709386200, 1709389800,
          1709393400, 1709397000, 1709400600, 1709404200, 1709407800, 1709411400, 1709415000,
          1709418600, 1709422200, 1709425800, 1709429400, 1709433000, 1709436600, 1709440200,
          1709443800, 1709447400, 1709451000, 1709454600, 1709458200, 1709461800, 1709465400,
          1709469000, 1709472600, 1709476200, 1709479800, 1709483400, 1709487000,
        ],
        values: [
          8, 5, 1, 0, 2, 2, 3, 6, 7, 13, 15, 13, 12, 15, 12, 19, 20, 10, 12, 9, 9, 10, 16, 12, 14,
          9, 3, 3, 1, 1, 0, 7, 7, 8, 21, 12, 9, 16, 9, 8, 7, 7, 13, 9, 11, 14, 15, 13,
        ],
        unit: 'count',
        updated_at: 1709487000,
      },
      average_order_value: {
        title: 'Average Order Value',
        timestamps: [
          1709317800, 1709321400, 1709325000, 1709328600, 1709332200, 1709335800, 1709339400,
          1709343000, 1709346600, 1709350200, 1709353800, 1709357400, 1709361000, 1709364600,
          1709368200, 1709371800, 1709375400, 1709379000, 1709382600, 1709386200, 1709389800,
          1709393400, 1709397000, 1709400600, 1709404200, 1709407800, 1709411400, 1709415000,
          1709418600, 1709422200, 1709425800, 1709429400, 1709433000, 1709436600, 1709440200,
          1709443800, 1709447400, 1709451000, 1709454600, 1709458200, 1709461800, 1709465400,
          1709469000, 1709472600, 1709476200, 1709479800, 1709483400, 1709487000,
        ],
        values: [
          119497, 64696, 41140, 0, 83250, 125672, 66496, 67116, 66047, 171855, 97656, 86475, 96754,
          129489, 96773, 91124, 85579, 119280, 66540, 82136, 81792, 562693, 118008, 89465, 88772,
          115822, 133180, 104745, 51570, 75420, 0, 157615, 90482, 109741, 109197, 71897, 76006,
          126286, 115887, 180685, 104906, 105955, 94778, 73975, 83975, 93213, 103322, 84492,
        ],
        unit: 'paise',
        updated_at: 1709487000,
      },
      prepay_vs_cod: {
        title: 'Prepaid vs COD orders',
        timestamps: [
          1709317800, 1709321400, 1709325000, 1709328600, 1709332200, 1709335800, 1709339400,
          1709343000, 1709346600, 1709350200, 1709353800, 1709357400, 1709361000, 1709364600,
          1709368200, 1709371800, 1709375400, 1709379000, 1709382600, 1709386200, 1709389800,
          1709393400, 1709397000, 1709400600, 1709404200, 1709407800, 1709411400, 1709415000,
          1709418600, 1709422200, 1709425800, 1709429400, 1709433000, 1709436600, 1709440200,
          1709443800, 1709447400, 1709451000, 1709454600, 1709458200, 1709461800, 1709465400,
          1709469000, 1709472600, 1709476200, 1709479800, 1709483400, 1709487000,
        ],
        total_sales: {
          values: [
            {
              label: 'COD',
              values: [
                817600, 271910, 41140, 0, 166500, 0, 124610, 346990, 394290, 672735, 752395, 677390,
                380405, 560735, 758130, 1077135, 1064260, 805600, 544500, 352060, 558620, 5542425,
                1325545, 792620, 715010, 649265, 399540, 151970, 51570, 75420, 0, 371450, 526105,
                364870, 1319905, 594935, 523350, 712250, 593590, 1445480, 409805, 442565, 1028505,
                619480, 681520, 925385, 1126090, 757290,
              ],
            },
            {
              label: 'Prepaid',
              values: [
                138380, 51570, 0, 0, 0, 251345, 74880, 55710, 68040, 1561385, 712455, 446795,
                780650, 1381600, 403155, 654230, 647320, 387200, 253980, 387170, 177510, 84510,
                562590, 280965, 527810, 393140, 0, 162265, 0, 0, 0, 731860, 107270, 513060, 973245,
                267840, 160710, 1308340, 449400, 0, 324540, 299125, 203615, 46300, 242210, 379610,
                423740, 341110,
              ],
            },
          ],
          unit: 'paise',
          updated_at: 1709487000,
        },
        total_orders_placed: {
          values: [
            {
              label: 'COD',
              values: [
                7, 4, 1, 0, 2, 0, 2, 5, 6, 7, 9, 7, 5, 7, 9, 11, 13, 9, 8, 5, 6, 9, 13, 9, 9, 7, 3,
                2, 1, 1, 0, 5, 6, 4, 15, 8, 6, 10, 5, 8, 5, 5, 11, 8, 7, 11, 12, 9,
              ],
            },
            {
              label: 'Prepaid',
              values: [
                1, 1, 0, 0, 0, 2, 1, 1, 1, 6, 6, 6, 7, 8, 3, 8, 7, 1, 4, 4, 3, 1, 3, 3, 5, 2, 0, 1,
                0, 0, 0, 2, 1, 4, 6, 4, 3, 6, 4, 0, 2, 2, 2, 1, 4, 3, 3, 4,
              ],
            },
          ],
          unit: 'count',
          updated_at: 1709487000,
        },
      },
      traffic_utm: {
        title: 'Traffic by UTM parameters',
        utm_source: [
          {
            label: 'Adyogi',
            sales: 26472720,
            order_count: 284,
            utm_medium: [
              {
                label: 'Ig',
                sales: 20589270,
                order_count: 212,
                utm_campaign: [
                  {
                    label:
                      'Berr_13853_adyogi_catalogsales_prospect_dresses Min Roas-120202988459370707',
                    sales: 1255520,
                    order_count: 16,
                  },
                  {
                    label: 'Berr_13853_adyogi_conversions_engage_all-23862388528160706',
                    sales: 1587170,
                    order_count: 16,
                  },
                  {
                    label: 'Berr_13853_adyogi_conversions_prospect_atc 19Th Jan-120205277485860707',
                    sales: 331450,
                    order_count: 5,
                  },
                  {
                    label: 'Dynamic Campaign-120207160562200707',
                    sales: 318140,
                    order_count: 2,
                  },
                  {
                    label: 'Berr_13853_adyogi_conversions_prospect_bags-120200173849720707',
                    sales: 1601715,
                    order_count: 14,
                  },
                  {
                    label: 'Berr_13853_adyogi_catalogsales_dynamic_all New-23862200676730706',
                    sales: 734275,
                    order_count: 8,
                  },
                  {
                    label:
                      'Berr_13853_adyogi_catalogsales_prospect_selected_value-120201291182040707',
                    sales: 1506300,
                    order_count: 21,
                  },
                  {
                    label: 'Berr_13853_adyogi_conversions_prospect_curve',
                    sales: 2425715,
                    order_count: 20,
                  },
                  {
                    label: 'Berr_13853_adyogi_conversions_prospect_curve-23859951640510706',
                    sales: 4896455,
                    order_count: 54,
                  },
                  {
                    label: 'Partnership Ad-120206795893820707',
                    sales: 3411450,
                    order_count: 21,
                  },
                  {
                    label:
                      'Berr_13853_adyogi_conversions_prospect_advantage_plus_all-23859952102950706',
                    sales: 1469200,
                    order_count: 21,
                  },
                  {
                    label:
                      'Berr_13853_adyogi_catalogsales_dynamic_MagicMagic Curve-120204916513390707',
                    sales: 1051880,
                    order_count: 14,
                  },
                ],
              },
              {
                label: 'Fb',
                sales: 5883450,
                order_count: 72,
                utm_campaign: [
                  {
                    label:
                      'Berr_13853_adyogi_catalogsales_prospect_dresses Min Roas-120202988459370707',
                    sales: 614895,
                    order_count: 6,
                  },
                  {
                    label:
                      'Berr_13853_adyogi_catalogsales_prospect_selected_value-120201291182040707',
                    sales: 632025,
                    order_count: 10,
                  },
                  {
                    label:
                      'Berr_13853_adyogi_catalogsales_dynamic_MagicMagic Curve-120204916513390707',
                    sales: 1279685,
                    order_count: 15,
                  },
                  {
                    label: 'Berr_13853_adyogi_conversions_prospect_curve-23859951640510706',
                    sales: 1563480,
                    order_count: 18,
                  },
                  {
                    label: 'Berr_13853_adyogi_conversions_prospect_bags-120200173849720707',
                    sales: 169800,
                    order_count: 2,
                  },
                  {
                    label:
                      'Berr_13853_adyogi_conversions_prospect_advantage_plus_all-23859952102950706',
                    sales: 986065,
                    order_count: 13,
                  },
                  {
                    label: 'Dynamic Campaign-120207160562200707',
                    sales: 360030,
                    order_count: 4,
                  },
                  {
                    label: 'Partnership Ad-120206795893820707',
                    sales: 34830,
                    order_count: 1,
                  },
                  {
                    label: 'Berr_13853_adyogi_catalogsales_dynamic_all New-23862200676730706',
                    sales: 242640,
                    order_count: 3,
                  },
                ],
              },
            ],
          },
          {
            label: 'Facebook',
            sales: 4807310,
            order_count: 46,
            utm_medium: [
              {
                label: 'Paid',
                sales: 2189380,
                order_count: 20,
                utm_campaign: [
                  {
                    label: 'Others',
                    sales: 2189380,
                    order_count: 20,
                  },
                ],
              },
              {
                label: 'Others',
                sales: 2617930,
                order_count: 26,
                utm_campaign: [
                  {
                    label: 'Others',
                    sales: 2617930,
                    order_count: 26,
                  },
                ],
              },
            ],
          },
          {
            label: 'Direct',
            sales: 17678680,
            order_count: 112,
            utm_medium: [
              {
                label: 'Direct',
                sales: 17678680,
                order_count: 112,
                utm_campaign: [
                  {
                    label: 'Direct',
                    sales: 17678680,
                    order_count: 112,
                  },
                ],
              },
            ],
          },
          {
            label: 'Igshopping',
            sales: 240750,
            order_count: 3,
            utm_medium: [
              {
                label: 'Social',
                sales: 240750,
                order_count: 3,
                utm_campaign: [
                  {
                    label: 'Others',
                    sales: 240750,
                    order_count: 3,
                  },
                ],
              },
            ],
          },
          {
            label: 'Bitespeed',
            sales: 366575,
            order_count: 2,
            utm_medium: [
              {
                label: 'Whatsapp',
                sales: 110415,
                order_count: 1,
                utm_campaign: [
                  {
                    label: 'Checkout Abandonment - Wa',
                    sales: 110415,
                    order_count: 1,
                  },
                ],
              },
              {
                label: 'Email',
                sales: 256160,
                order_count: 1,
                utm_campaign: [
                  {
                    label: 'Broadcast Feb 23, 2024',
                    sales: 256160,
                    order_count: 1,
                  },
                ],
              },
            ],
          },
          {
            label: 'Referral',
            sales: 163540,
            order_count: 1,
            utm_medium: [
              {
                label: 'Post',
                sales: 163540,
                order_count: 1,
                utm_campaign: [
                  {
                    label: 'Loox-Social',
                    sales: 163540,
                    order_count: 1,
                  },
                ],
              },
            ],
          },
        ],
        unit: 'paise',
        updated_at: 1709487000,
      },
      top_selling_products: {
        title: 'Top Selling Products',
        values: [
          {
            label: 'Jeans',
            qty: 20,
            gmv: 2880000,
          },
          {
            label: 'T-shirt',
            qty: 15,
            gmv: 2208000,
          },
          {
            label: 'Shirt',
            qty: 30,
            gmv: 1941000,
          },
        ],
        updated_at: 1709487000,
      },
      conversion_rate: {
        title: 'Conversion Rate',
        timestamps: [1709317800, 1709404200],
        values: [42.963000000000001, 0],
        unit: 'percent',
        updated_at: 1709404200,
      },
      conversion_rate_logged_in: {
        title: 'Conversion Rate Logged In',
        timestamps: [1709317800, 1709404200],
        values: [62.963000000000001, 0],
        unit: 'percent',
        updated_at: 1709404200,
      },
      conversion_rate_logged_out: {
        title: 'Conversion Rate Logged Out',
        timestamps: [1709317800, 1709404200],
        values: [22.963000000000001, 0],
        unit: 'percent',
        updated_at: 1709404200,
      },
    },
  },
};

export const ORDER_ANALYTICS_WITH_ONLY_CONVERSION_RATE = {
  isFetching: false,
  analyticsData: {
    metrics: {
      total_sales: {
        title: 'Total Sales',
        timestamps: [
          1709317800, 1709321400, 1709325000, 1709328600, 1709332200, 1709335800, 1709339400,
          1709343000, 1709346600, 1709350200, 1709353800, 1709357400, 1709361000, 1709364600,
          1709368200, 1709371800, 1709375400, 1709379000, 1709382600, 1709386200, 1709389800,
          1709393400, 1709397000, 1709400600, 1709404200, 1709407800, 1709411400, 1709415000,
          1709418600, 1709422200, 1709425800, 1709429400, 1709433000, 1709436600, 1709440200,
          1709443800, 1709447400, 1709451000, 1709454600, 1709458200, 1709461800, 1709465400,
          1709469000, 1709472600, 1709476200, 1709479800, 1709483400, 1709487000,
        ],
        values: [
          955980, 323480, 41140, 0, 166500, 251345, 199490, 402700, 462330, 2234120, 1464850,
          1124185, 1161055, 1942335, 1161285, 1731365, 1711580, 1192800, 798480, 739230, 736130,
          5626935, 1888135, 1073585, 1242820, 1042405, 399540, 314235, 51570, 75420, 0, 1103310,
          633375, 877930, 2293150, 862775, 684060, 2020590, 1042990, 1445480, 734345, 741690,
          1232120, 665780, 923730, 1304995, 1549830, 1098400,
        ],
        unit: 'paise',
        updated_at: 1709487000,
      },
      total_orders_placed: {
        title: 'Total Orders Placed',
        timestamps: [
          1709317800, 1709321400, 1709325000, 1709328600, 1709332200, 1709335800, 1709339400,
          1709343000, 1709346600, 1709350200, 1709353800, 1709357400, 1709361000, 1709364600,
          1709368200, 1709371800, 1709375400, 1709379000, 1709382600, 1709386200, 1709389800,
          1709393400, 1709397000, 1709400600, 1709404200, 1709407800, 1709411400, 1709415000,
          1709418600, 1709422200, 1709425800, 1709429400, 1709433000, 1709436600, 1709440200,
          1709443800, 1709447400, 1709451000, 1709454600, 1709458200, 1709461800, 1709465400,
          1709469000, 1709472600, 1709476200, 1709479800, 1709483400, 1709487000,
        ],
        values: [
          8, 5, 1, 0, 2, 2, 3, 6, 7, 13, 15, 13, 12, 15, 12, 19, 20, 10, 12, 9, 9, 10, 16, 12, 14,
          9, 3, 3, 1, 1, 0, 7, 7, 8, 21, 12, 9, 16, 9, 8, 7, 7, 13, 9, 11, 14, 15, 13,
        ],
        unit: 'count',
        updated_at: 1709487000,
      },
      average_order_value: {
        title: 'Average Order Value',
        timestamps: [
          1709317800, 1709321400, 1709325000, 1709328600, 1709332200, 1709335800, 1709339400,
          1709343000, 1709346600, 1709350200, 1709353800, 1709357400, 1709361000, 1709364600,
          1709368200, 1709371800, 1709375400, 1709379000, 1709382600, 1709386200, 1709389800,
          1709393400, 1709397000, 1709400600, 1709404200, 1709407800, 1709411400, 1709415000,
          1709418600, 1709422200, 1709425800, 1709429400, 1709433000, 1709436600, 1709440200,
          1709443800, 1709447400, 1709451000, 1709454600, 1709458200, 1709461800, 1709465400,
          1709469000, 1709472600, 1709476200, 1709479800, 1709483400, 1709487000,
        ],
        values: [
          119497, 64696, 41140, 0, 83250, 125672, 66496, 67116, 66047, 171855, 97656, 86475, 96754,
          129489, 96773, 91124, 85579, 119280, 66540, 82136, 81792, 562693, 118008, 89465, 88772,
          115822, 133180, 104745, 51570, 75420, 0, 157615, 90482, 109741, 109197, 71897, 76006,
          126286, 115887, 180685, 104906, 105955, 94778, 73975, 83975, 93213, 103322, 84492,
        ],
        unit: 'paise',
        updated_at: 1709487000,
      },
      prepay_vs_cod: {
        title: 'Prepaid vs COD orders',
        timestamps: [
          1709317800, 1709321400, 1709325000, 1709328600, 1709332200, 1709335800, 1709339400,
          1709343000, 1709346600, 1709350200, 1709353800, 1709357400, 1709361000, 1709364600,
          1709368200, 1709371800, 1709375400, 1709379000, 1709382600, 1709386200, 1709389800,
          1709393400, 1709397000, 1709400600, 1709404200, 1709407800, 1709411400, 1709415000,
          1709418600, 1709422200, 1709425800, 1709429400, 1709433000, 1709436600, 1709440200,
          1709443800, 1709447400, 1709451000, 1709454600, 1709458200, 1709461800, 1709465400,
          1709469000, 1709472600, 1709476200, 1709479800, 1709483400, 1709487000,
        ],
        total_sales: {
          values: [
            {
              label: 'COD',
              values: [
                817600, 271910, 41140, 0, 166500, 0, 124610, 346990, 394290, 672735, 752395, 677390,
                380405, 560735, 758130, 1077135, 1064260, 805600, 544500, 352060, 558620, 5542425,
                1325545, 792620, 715010, 649265, 399540, 151970, 51570, 75420, 0, 371450, 526105,
                364870, 1319905, 594935, 523350, 712250, 593590, 1445480, 409805, 442565, 1028505,
                619480, 681520, 925385, 1126090, 757290,
              ],
            },
            {
              label: 'Prepaid',
              values: [
                138380, 51570, 0, 0, 0, 251345, 74880, 55710, 68040, 1561385, 712455, 446795,
                780650, 1381600, 403155, 654230, 647320, 387200, 253980, 387170, 177510, 84510,
                562590, 280965, 527810, 393140, 0, 162265, 0, 0, 0, 731860, 107270, 513060, 973245,
                267840, 160710, 1308340, 449400, 0, 324540, 299125, 203615, 46300, 242210, 379610,
                423740, 341110,
              ],
            },
          ],
          unit: 'paise',
          updated_at: 1709487000,
        },
        total_orders_placed: {
          values: [
            {
              label: 'COD',
              values: [
                7, 4, 1, 0, 2, 0, 2, 5, 6, 7, 9, 7, 5, 7, 9, 11, 13, 9, 8, 5, 6, 9, 13, 9, 9, 7, 3,
                2, 1, 1, 0, 5, 6, 4, 15, 8, 6, 10, 5, 8, 5, 5, 11, 8, 7, 11, 12, 9,
              ],
            },
            {
              label: 'Prepaid',
              values: [
                1, 1, 0, 0, 0, 2, 1, 1, 1, 6, 6, 6, 7, 8, 3, 8, 7, 1, 4, 4, 3, 1, 3, 3, 5, 2, 0, 1,
                0, 0, 0, 2, 1, 4, 6, 4, 3, 6, 4, 0, 2, 2, 2, 1, 4, 3, 3, 4,
              ],
            },
          ],
          unit: 'count',
          updated_at: 1709487000,
        },
      },
      traffic_utm: {
        title: 'Traffic by UTM parameters',
        utm_source: [
          {
            label: 'Adyogi',
            sales: 26472720,
            order_count: 284,
            utm_medium: [
              {
                label: 'Ig',
                sales: 20589270,
                order_count: 212,
                utm_campaign: [
                  {
                    label:
                      'Berr_13853_adyogi_catalogsales_prospect_dresses Min Roas-120202988459370707',
                    sales: 1255520,
                    order_count: 16,
                  },
                  {
                    label: 'Berr_13853_adyogi_conversions_engage_all-23862388528160706',
                    sales: 1587170,
                    order_count: 16,
                  },
                  {
                    label: 'Berr_13853_adyogi_conversions_prospect_atc 19Th Jan-120205277485860707',
                    sales: 331450,
                    order_count: 5,
                  },
                  {
                    label: 'Dynamic Campaign-120207160562200707',
                    sales: 318140,
                    order_count: 2,
                  },
                  {
                    label: 'Berr_13853_adyogi_conversions_prospect_bags-120200173849720707',
                    sales: 1601715,
                    order_count: 14,
                  },
                  {
                    label: 'Berr_13853_adyogi_catalogsales_dynamic_all New-23862200676730706',
                    sales: 734275,
                    order_count: 8,
                  },
                  {
                    label:
                      'Berr_13853_adyogi_catalogsales_prospect_selected_value-120201291182040707',
                    sales: 1506300,
                    order_count: 21,
                  },
                  {
                    label: 'Berr_13853_adyogi_conversions_prospect_curve',
                    sales: 2425715,
                    order_count: 20,
                  },
                  {
                    label: 'Berr_13853_adyogi_conversions_prospect_curve-23859951640510706',
                    sales: 4896455,
                    order_count: 54,
                  },
                  {
                    label: 'Partnership Ad-120206795893820707',
                    sales: 3411450,
                    order_count: 21,
                  },
                  {
                    label:
                      'Berr_13853_adyogi_conversions_prospect_advantage_plus_all-23859952102950706',
                    sales: 1469200,
                    order_count: 21,
                  },
                  {
                    label:
                      'Berr_13853_adyogi_catalogsales_dynamic_MagicMagic Curve-120204916513390707',
                    sales: 1051880,
                    order_count: 14,
                  },
                ],
              },
              {
                label: 'Fb',
                sales: 5883450,
                order_count: 72,
                utm_campaign: [
                  {
                    label:
                      'Berr_13853_adyogi_catalogsales_prospect_dresses Min Roas-120202988459370707',
                    sales: 614895,
                    order_count: 6,
                  },
                  {
                    label:
                      'Berr_13853_adyogi_catalogsales_prospect_selected_value-120201291182040707',
                    sales: 632025,
                    order_count: 10,
                  },
                  {
                    label:
                      'Berr_13853_adyogi_catalogsales_dynamic_MagicMagic Curve-120204916513390707',
                    sales: 1279685,
                    order_count: 15,
                  },
                  {
                    label: 'Berr_13853_adyogi_conversions_prospect_curve-23859951640510706',
                    sales: 1563480,
                    order_count: 18,
                  },
                  {
                    label: 'Berr_13853_adyogi_conversions_prospect_bags-120200173849720707',
                    sales: 169800,
                    order_count: 2,
                  },
                  {
                    label:
                      'Berr_13853_adyogi_conversions_prospect_advantage_plus_all-23859952102950706',
                    sales: 986065,
                    order_count: 13,
                  },
                  {
                    label: 'Dynamic Campaign-120207160562200707',
                    sales: 360030,
                    order_count: 4,
                  },
                  {
                    label: 'Partnership Ad-120206795893820707',
                    sales: 34830,
                    order_count: 1,
                  },
                  {
                    label: 'Berr_13853_adyogi_catalogsales_dynamic_all New-23862200676730706',
                    sales: 242640,
                    order_count: 3,
                  },
                ],
              },
            ],
          },
          {
            label: 'Facebook',
            sales: 4807310,
            order_count: 46,
            utm_medium: [
              {
                label: 'Paid',
                sales: 2189380,
                order_count: 20,
                utm_campaign: [
                  {
                    label: 'Others',
                    sales: 2189380,
                    order_count: 20,
                  },
                ],
              },
              {
                label: 'Others',
                sales: 2617930,
                order_count: 26,
                utm_campaign: [
                  {
                    label: 'Others',
                    sales: 2617930,
                    order_count: 26,
                  },
                ],
              },
            ],
          },
          {
            label: 'Direct',
            sales: 17678680,
            order_count: 112,
            utm_medium: [
              {
                label: 'Direct',
                sales: 17678680,
                order_count: 112,
                utm_campaign: [
                  {
                    label: 'Direct',
                    sales: 17678680,
                    order_count: 112,
                  },
                ],
              },
            ],
          },
          {
            label: 'Igshopping',
            sales: 240750,
            order_count: 3,
            utm_medium: [
              {
                label: 'Social',
                sales: 240750,
                order_count: 3,
                utm_campaign: [
                  {
                    label: 'Others',
                    sales: 240750,
                    order_count: 3,
                  },
                ],
              },
            ],
          },
          {
            label: 'Bitespeed',
            sales: 366575,
            order_count: 2,
            utm_medium: [
              {
                label: 'Whatsapp',
                sales: 110415,
                order_count: 1,
                utm_campaign: [
                  {
                    label: 'Checkout Abandonment - Wa',
                    sales: 110415,
                    order_count: 1,
                  },
                ],
              },
              {
                label: 'Email',
                sales: 256160,
                order_count: 1,
                utm_campaign: [
                  {
                    label: 'Broadcast Feb 23, 2024',
                    sales: 256160,
                    order_count: 1,
                  },
                ],
              },
            ],
          },
          {
            label: 'Referral',
            sales: 163540,
            order_count: 1,
            utm_medium: [
              {
                label: 'Post',
                sales: 163540,
                order_count: 1,
                utm_campaign: [
                  {
                    label: 'Loox-Social',
                    sales: 163540,
                    order_count: 1,
                  },
                ],
              },
            ],
          },
        ],
        unit: 'paise',
        updated_at: 1709487000,
      },
      top_selling_products: {
        title: 'Top Selling Products',
        values: [
          {
            label: 'Jeans',
            qty: 20,
            gmv: 2880000,
          },
          {
            label: 'T-shirt',
            qty: 15,
            gmv: 2208000,
          },
          {
            label: 'Shirt',
            qty: 30,
            gmv: 1941000,
          },
        ],
        updated_at: 1709487000,
      },
      conversion_rate: {
        title: 'Conversion Rate',
        timestamps: [1709317800, 1709404200],
        values: [42.963000000000001, 0],
        unit: 'percent',
        updated_at: 1709404200,
      },
    },
  },
};
