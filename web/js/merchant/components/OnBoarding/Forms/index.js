import RouteForm from './Routes';

const FORM_TYPE = user => {
  let prefix = '';
  if (user.isOrgRZP) {
    prefix = 'Razorpay';
  }

  return {
    marketplace: {
      formComponent: RouteForm,
      links: {
        docs: 'https://razorpay.com/docs/route',
        knowMore: 'https://razorpay.com/route',
      },
      formText: `We'd require the following details to enable ${prefix} Route on your account.`,
    },
  };
};

export default FORM_TYPE;
