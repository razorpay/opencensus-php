export const initialState = {
  session: {
    user: {
      isRefundAllowed: true,
      isOrgAllowedFunctionality: (_) => true,
    },
  },
};

export const paymentPageProps = {
  location: {
    pathname: 'https://dashboard.dev.razorpay.in/app/payments/pay_MIhWX1djl9gU5r',
  },
  match: {
    params: {
      id: 'pay_MIhWX1djl9gU5r',
    },
  },
};

export const refundPageProps = {
  location: {
    pathname: 'https://dashboard.dev.razorpay.in/app/refunds/pay_MIhWX1djl9gU5r',
  },
  match: {
    params: {
      id: 'rfnd_MP7ohriq3n09Pn',
    },
  },
};
