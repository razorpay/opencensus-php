const baseMeta = {
  key: 'product',
  label: 'Product Sale',
  card: {
    title: 'Product Sale',
    description:
      'Kickstart your sales by adding product description along with multiple images and shipping information.',
    img: '/img/payment_pages/product_sale.jpg',
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
      insert: '# Select and upload some images of your product / service\n\nProduct Description ',
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
      insert: '# Time required to prepare order for shipment in days\n\nAverage delivery time ',
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

export default {
  IN: baseMeta,
  MY: baseMeta,
};
