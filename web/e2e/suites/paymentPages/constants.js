export const PAYMENT_PAGES_TYPES = {
  payment_page: 'payment_pages',
  storefront: 'storefront',
  batch_payment_page: 'batch_payment_page',
};

const product1 = {
  product_name: 'Product 1',
  description: 'Product 1 description',
  amount: '200',
  discounted_amount: '',
  currency: 'INR',
  status: 'unlimited',
  units: '',
  images: [],
};

// const product2 = {
//   product_name: 'Product 2',
//   description: 'Product 2 description',
//   amount: '500',
//   discounted_amount: '199.99',
//   currency: 'INR',
//   status: 'in_stock',
//   units: '1000',
//   images: [],
// };

export const paymentPagesEcommerceData = {
  default: {
    title: 'Store title 1',
    currency: 'INR',
    type: 'store',
    support_email: 'test@razorpay.com',
    support_contact: '12232323232',
    mode: 'test',
    products: [product1],
    expire_by: null,
    slug: '',
  },
};

export const batchPaymentPageData = {
  detailsPage: {
    paymentLinkId: 'pl_NBIHnwkjVUIseX',
  },
  page_title: 'Batch PP title',
  support_email: 'test@razorpay.com',
  support_contact: '12232323232',
};
