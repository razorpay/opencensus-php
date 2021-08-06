const productMap = {
  '1': {
    name: 'Payment link',
    description:
      'Start sharing payment links via an email, SMS, chatbot etc. and get paid immediatly.',
    imageCdn: 'https://cdn.razorpay.com/static/assets/product-recommendation/payment-link.svg',
    redirectUrl: '/paymentlinks',
    segmentEventName: 'Product recommendation PL',
  },
  '2': {
    name: 'Payment Gateway',
    description: 'Add payments to your website or app using our API keys.',
    imageCdn: 'https://cdn.razorpay.com/static/assets/product-recommendation/payment-geteway.svg',
    redirectUrl: '/keys',
    segmentEventName: 'Product recommendation PG',
  },
  '3': {
    name: 'Payment Pages',
    description:
      'Create custom-branded, hosted Payment Pages in a few clicks to accept payments online.',
    imageCdn: 'https://cdn.razorpay.com/static/assets/product-recommendation/payment-page.svg',
    redirectUrl: '/paymentpages',
    segmentEventName: 'Product recommendation PP',
  },
  '4': {
    name: 'Subscriptions',
    description: 'Collect recurring payments from customers with Razorpay Subscriptions APIs',
    imageCdn: 'https://cdn.razorpay.com/static/assets/product-recommendation/subscriptions.svg',
    redirectUrl: '/subscriptions',
    segmentEventName: 'Product recommendation subscriptions',
  },
  '5': {
    name: 'Route',
    description:
      'Easily split payments or automate routing money with complete control over the business logic.',
    imageCdn: 'https://cdn.razorpay.com/static/assets/product-recommendation/route.svg',
    redirectUrl: '/route/payments',
    segmentEventName: 'Product recommendation Route',
  },
  '6': {
    name: 'Smart Collect',
    description:
      'Create and send GST compliant invoices that your customers can pay online instantly.',
    imageCdn: 'https://cdn.razorpay.com/static/assets/product-recommendation/smart-collect.svg',
    redirectUrl: '/smartcollect/payments',
    segmentEventName: 'Product recommendation smart collect',
  },
  '7': {
    name: 'Payment Buttons',
    description:
      'Collect payments on your websites and blogs with a single line of code without integration.',
    imageCdn: 'https://cdn.razorpay.com/static/assets/product-recommendation/payment-button.svg',
    redirectUrl: '/paymentbuttons',
    segmentEventName: 'Product recommendation PB',
  },
};

const activeProductIDOrder = {
  payment_gateway: ['2', '7', '1'],
  payment_page: ['3', '1', '2'],
  payment_link: ['1', '3', '2'],
  payment_button: ['7', '2', '1'],
  smart_collect: ['6', '1', '3'],
  route: ['5', '1', '3'],
  subscriptions: ['4', '1', '3'],
};

export const getRecommendedProduct = (activeProduct) => {
  const filterProduct = activeProductIDOrder[activeProduct]
    ? activeProductIDOrder[activeProduct].reduce((acc, curr) => {
        if (productMap[curr]) {
          acc.push(productMap[curr]);
        }
        return acc;
      }, [])
    : null;
  return filterProduct;
};
