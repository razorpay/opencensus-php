import RouteForm from './Routes';
import SubscriptionsForm from './Subscriptions';
import VirtualAccountsForm from './VirtualAccounts';

import SubscriptionsPreStep from '../PreStepForms/Subscriptions';

const FORM_TYPE = {
  marketplace: {
    formComponent: RouteForm,
    links: {
      docs: 'https://razorpay.com/docs/route',
      knowMore: 'https://razorpay.com/route',
    },
    formText:
      "We'd require the following details to enable Razorpay Route on your account.",
  },

  subscriptions: {
    formComponent: SubscriptionsForm,
    title: 'Business Model, Plans',
    preStep: {
      component: SubscriptionsPreStep,
      title: 'Website/App Link',
    },
    links: {
      docs: 'https://razorpay.com/docs/subscriptions',
      knowMore: 'https://razorpay.com/subscriptions',
    },
    formText:
      "We'd require the following details to enable Razorpay Subscriptions on your account.",
  },

  virtual_accounts: {
    formComponent: VirtualAccountsForm,
    links: {
      docs: 'https://razorpay.com/docs/smart-collect',
      knowMore: 'https://razorpay.com/smartcollect',
    },
    formText:
      "We'd require the following details to enable Razorpay Smart Collect on your account.",
  },
};

export default FORM_TYPE;
