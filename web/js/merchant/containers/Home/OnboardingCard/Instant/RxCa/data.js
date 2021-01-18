//TODO
// update content
import { currentAccountStatuses } from './Cards/data';
import Button from 'common/new-ui/Button';

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

export const getCaState = (caAccountStatus, GoToCaDocs) => {
  let pillType,
    pillText,
    content,
    headState = '',
    viewType = '',
    title = '';
  if (!caAccountStatus || caAccountStatus === currentAccountStatuses.created) {
    pillType = 'default';
    pillText = 'Request Received';
    viewType = 'default';
    content = (
      <>
        <span>
          Our executive will contact you soon. You can get the application documents ready as per
          your business category.
        </span>{' '}
        <Button.Transparent className="view-doc" onClick={GoToCaDocs}>
          View Documents
        </Button.Transparent>
      </>
    );
  } else if (caAccountStatus === currentAccountStatuses.picked) {
    pillType = 'default';
    pillText = 'Process Started';
    viewType = 'default';
    content = (
      <>
        <span>
          RazorpayX has started the application process. You can get the application documents ready
          as per your business category.
        </span>{' '}
        <Button.Transparent className="view-doc" onClick={GoToCaDocs}>
          View Documents
        </Button.Transparent>
      </>
    );
  } else if (caAccountStatus === currentAccountStatuses.processed) {
    pillType = 'yellow';
    pillText = 'Activation In Progress';
    headState = 'Account opened';
    viewType = 'default';
    content = (
      <>
        Your account has been opened RazorpayX is working with the banking partner to get your
        Current Account activated.
      </>
    );
  } else if (
    caAccountStatus === currentAccountStatuses.processing ||
    caAccountStatus === currentAccountStatuses.initiated
  ) {
    pillType = 'yellow';
    pillText = 'Bank KYC In Progress';
    headState = 'Documents Recieved';
    viewType = 'default';
    content = (
      <>
        RBL bank has received your form and documents and is working to complete your application
        process. We are working with our banking partner to get the latest status of your KYC
      </>
    );
  } else if (caAccountStatus === currentAccountStatuses.cancelled) {
    pillType = 'danger';
    pillText = 'Request Cancelled';
    viewType = 'announcement';
    title = 'Current account request cancelled';
    content = (
      <>
        Your current account application has been cancelled. You have been reverted back to classinc
        pricing with 2% transaction fees{' '}
      </>
    );
  } else if (caAccountStatus === currentAccountStatuses.unserviceable) {
    pillType = 'grey';
    pillText = 'Unserviceable';
    viewType = 'default';
    title = '';
    content = (
      <>
        Unfortunately, our banking partner can't service at your location currently. However, you
        can keep using the RazorpayX Virtual Account.
      </>
    );
  } else if (caAccountStatus === currentAccountStatuses.rejected) {
    pillType = 'danger';
    pillText = 'Request Rejected';
    viewType = 'announcement';
    title = 'Current account request rejected';
    content = (
      <>
        Your application has been rejected by our banking partner. You have been reverted to classic
        pricing with 2% transaction rate
      </>
    );
  } else if (caAccountStatus === currentAccountStatuses.activated) {
    pillType = 'success';
    pillText = 'Account Activated';
    viewType = 'default';
    content = (
      <>
        Your current account is now active, and you’re ready to take off! Start enjoying the
        benefits of your new account. Your payments will now be settled in this account
      </>
    );
  }

  return { pillType, pillText, content, headState, title, viewType };
};
