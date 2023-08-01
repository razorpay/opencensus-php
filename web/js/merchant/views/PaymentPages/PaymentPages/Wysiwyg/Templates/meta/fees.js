const baseMeta = {
  key: 'fees',
  label: 'Fees Collection',
  card: {
    title: 'Fees Collection',
    description: 'Collect fees online by adding program details, fee breakup and T&C.',
    img: '/img/payment_pages/fee_collection.jpg',
  },
  quillPrefill: [
    {
      insert: 'Program name ',
    },
    {
      attributes: {
        header: 2,
      },
      insert: '\n',
    },
    {
      insert: '# Name of the course / workshop / membership\n\nProgram Description ',
    },
    {
      attributes: {
        header: 2,
      },
      insert: '\n',
    },
    {
      insert: '# Course description with highlights and benefits to attendees\n\nFee breakup ',
    },
    {
      attributes: {
        header: 2,
      },
      insert: '\n',
    },
    {
      insert:
        '# Fee structure for activites of the course / workshop / membership\n\nName of the organiser ',
    },
    {
      attributes: {
        header: 2,
      },
      insert: '\n',
    },
    {
      insert: '# Organisation / Organiser description with address and contact information\n',
    },
  ],
};

export const i18nEventsTicketsMeta = {
  ...baseMeta,
  card: {
    ...baseMeta.card,
    img: '/img/payment_pages/i18n/fee-collection.svg',
  },
};

export default {
  IN: baseMeta,
  MY: i18nEventsTicketsMeta,
};
