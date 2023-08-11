export const getInitialUserOrgState = ({
  isRzpOrg,
  userExtra = { merchant: {} },
  orgExtra = {},
}: {
  isRzpOrg: boolean;
  userExtra: any;
  orgExtra: any;
}) => ({
  user: {
    isOrgRZP: isRzpOrg,
    isOrgCurlec: !isRzpOrg,
    ...userExtra,
    merchant: {
      currency: isRzpOrg ? 'INR' : 'MYR',
      ...userExtra.merchant,
    },
  },
  org: {
    custom_code: isRzpOrg ? 'rzp' : 'curlec',
    business_name: isRzpOrg ? 'Razorpay' : 'Curlec',
    ...orgExtra,
  },
});
