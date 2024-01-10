export const programsResponse = {
  status_code: 200,
  success: true,
  data: {
    entity: 'collection',
    count: '8',
    items: [
      {
        id: 'iprog_NEC3fO5GTvvX2S',
        name: 'Inactive Gift Card',
        merchant: 'GCMS Devstack',
        type: 'giftcard',
        policies: {
          gift_card_brand_name: 'Swiggy',
          gift_card_pin_enabled: false,

          gift_card_ppi_type: 'non-ppi',
          gift_card_price_type: 'range',
          gift_card_reissue_enabled: true,
          gift_card_tnc_link: 'https://cdn.razorpay.com/static/assets/wallet/tncs.pdf',
          gift_card_validity_in_days: 10,

          image_link:
            'https://d1o7uku192uawx.cloudfront.net/mobile/media/catalog/product/a/m/amazon_312x200_21092022_2.png',
          max_discount_percent: '5',
          min_discount_percent: '2',
          ppi_type: 'non-ppi',
          program_category: 'Home Decor',
          program_desc: 'test description for Inactive gift cards',
          program_distribution: 'B2B',
          program_type: 'voucher',
        },
      },
    ],
  },
};
