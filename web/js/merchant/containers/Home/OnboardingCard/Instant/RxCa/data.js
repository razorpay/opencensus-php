//TODO
// update content
export const FAQ_DATA = [
  {
    ques: 'What happens to my existing settlement account on Razorpay?',
    ans:
      'Once a RazorpayX current account is created, your default settlement account on Razorpay will be automatically switched to this new current account.',
  },
  {
    ques: 'Is getting a RazorpayX current account mandatory?',
    ans:
      'A RazorpayX current account is mandatory for the NEO plan that gives you 1.75% pricing. However, if you don’t create a RazorpayX CA your pricing will switch back to the standard 2% pricing.',
  },
  {
    ques: 'What are the charges for RazorpayX current account?',
    ans: (
      <>
        Since you are on NEO plan, you get ₹33L worth free payouts. After this you will also get 500
        free payouts every month. If you’re doing more than 500 payouts/month we offer competitive
        pricing that can be{' '}
        <a href="https://razorpay.com/x/" target="_blank">
          found here
        </a>
      </>
    ),
  },
  {
    ques: 'What happens if I’m unable to create a RazorpayX CA in under 90 days?',
    ans:
      'If you’re unable to create a RazorpayX current account in under 90 days your pricing plan will switch back to the standard 2% pricing.',
  },
];

const validCoupons = ['NEORZP'];

export const hasNeoCouponCode = (coupons) => {
  return validCoupons.some((validCoupon) => coupons.includes(validCoupon));
};
