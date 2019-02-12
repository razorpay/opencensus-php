export default {
  label: 'Fees Collection',
  card: {
    title: 'Fees Collection',
    description:
      'Collect fees and accept payments in seconds with our online form, no paperwork involved.',
    img: '/img/payment_pages/fee_collection.jpg',
  },
  quillPrefill: [
    {
      insert: 'Product Image(s)',
    },
    {
      attributes: {
        header: 2,
      },
      insert: '\n',
    },
    {
      insert:
        '# Select and upload some images of your product / service\n\nProduct Description ',
    },
    {
      attributes: {
        header: 2,
      },
      insert: '\n',
    },
    {
      insert:
        '# Add product description with features and benefits\n\nShips in X days ( if physical product )',
    },
    {
      attributes: {
        header: 2,
      },
      insert: '\n',
    },
    {
      insert:
        '# Time required to prepare order for shipment in days\n\nAverage delivery time ',
    },
    {
      attributes: {
        header: 2,
      },
      insert: '\n',
    },
    {
      insert: '# Average time required for delivery after shipment\n',
    },
  ],
};
