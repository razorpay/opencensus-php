const FIELD_TYPES = {
  donation_amount_based: {
    label: 'Track amount based goal',
    short_description: 'Ideal for Fundraising & Crowdsourcing',
    key: 'donation_amount_based',
    icon: 'fixed_price',
    info: {
      img: '/img/payment_pages/amount-based.svg',
      description:
        'Show a progress for your project goals and help your supporters visualise their contributions',
    },
  },

  donation_supporter_based: {
    label: 'Track supporter based goal',
    short_description: 'Ideal for Events, Tickets & Product Sale',
    key: 'donation_supporter_based',
    icon: 'dynamic_price',
    info: {
      img: '/img/payment_pages/supporter-based.svg',
      description:
        'Show units sold, supporter count and days left for your events or sales to help convert potential customers.',
    },
  },
};

export default FIELD_TYPES;
