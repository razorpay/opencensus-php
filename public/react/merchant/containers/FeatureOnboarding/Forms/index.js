import RouteForm from './Routes';
import SubscriptionsForm from './Subscriptions';
import VirtualAccountsForm from './VirtualAccounts';

const FORM_TYPE = {
  marketplace: {
    formComponent: RouteForm,
    links: {
      docs: '',
      knowMore: '',
    },
    formText:
      'This is to help you get started with Razorpay Route. Here is how you can enable Razorpay Route on your dashboard.',
  },

  subscriptions: {
    formComponent: SubscriptionsForm,
    links: {
      docs: '',
      knowMore: '',
    },
    formText: '',
  },

  virtual_accounts: {
    formComponent: VirtualAccountsForm,
    links: {
      docs: '',
      knowMore: '',
    },
    formText: '',
  },
};

export default FORM_TYPE;
