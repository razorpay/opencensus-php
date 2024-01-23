const getSubscriptionDataRes = ({
  subscriptionStatus,
  paymentSubscriptionStatus,
  internalSubscription,
  type = 'PG',
}: {
  subscriptionStatus?: string;
  paymentSubscriptionStatus?: string;
  internalSubscription?: string;
  type?: 'PG' | 'INTERNAL';
} = {}) => {
  return {
    subscription: {
      id: 'LB9aJUmmTXU5rl',
      type,
      merchant_id: 'GJeIhxL2Ak2vDy',
      account_key: 'rzp_test_uA0ha3ZDeTHMB2',
      plan_id: 'LB9NO011N8zS13',
      frequency: 'monthly',
      payment_subscription_id: 'sub_LB9aIWgoDb88ra',
      status: subscriptionStatus || 'approved',
      next_charge_at: '1831527576',
      current_start: '1675249959',
      current_end: '1831527576',
      internal_subscription: {
        status: internalSubscription || 'pending',
      },
      payment_subscription: {
        id: 'sub_LB9aIWgoDb88ra',
        entity: 'subscription',
        plan_id: 'plan_LB9NNorpbWqog8',
        customer_id: 'cust_LB9wBw4yHY8vzD',
        status: paymentSubscriptionStatus || 'active',
        quantity: 1,
        end_at: '1704047400',
        total_count: 12,
        paid_count: 1,
        customer_notify: true,
        created_at: '1675248715',
        short_url: 'https://rzp.io/i/TXshYNG4oK',
        source: 'api',
        remaining_count: 11,
      },
      plan: {
        id: 'LB9NO011N8zS13',
        name: 'Get onboard plan',
        yearly_plan_id: 'plan_LB9NM81vm6P7GI',
        monthly_plan_id: 'plan_LB9NNorpbWqog8',
        yearly_plan_amount: '99900',
        monthly_plan_amount: '9900',
        details: {
          icon: {
            src: 'https://betacdn.np.razorpay.in/static/assets/growth-assets/pricing-bundle/growth.svg',
            alt: 'Growth Package',
          },
          feature: [
            {
              feature_copy: 'Receive Payments for FREE upto',
              offering: '₹ 50,000/month',
            },
            {
              feature_copy: 'Get guaranteed benefits and savings',
              offering: 'worth ₹2,08,000',
            },
            {
              feature_copy: 'Settlement Period',
              offering: 'EARLY SETTLEMENTS | T+1',
            },
            {
              feature_copy: 'Customer Support:',
              offering: 'PREMIUM | 48 Hour Resolution',
            },
            {
              feature_copy: 'Account Management',
              offering: 'Dedicated Account Manager',
            },
            {
              feature_copy: 'Transaction Fee post free limit',
              offering: '1.9% (5% Off)',
            },
            {
              feature_copy: 'Proprietary Reports',
              offering: 'Industry trends\nPeer product adoption',
            },
            {
              feature_copy: 'Exclusive Masterclasses and Consultant Access from Industry Experts',
              offering: 'Free worth Rs 30,000',
            },
            {
              feature_copy: 'Exclusive offers and Benefits at ₹0 additional cost:',
              offering:
                '10% off on Google Workspace on recurring billing\nShiprocket - 100% cash-back up to Rs. 1000',
            },
          ],
        },
        bundle: 'payment_gateway_pricing',
        created_by: 'aman.bharadwaj@razorpay.com',
        created_at: '2023-02-01T10:39:41Z',
        updated_at: '2023-02-01T10:39:41Z',
      },
    },
  } as const;
};

export { getSubscriptionDataRes };
