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
      'This is to help you get started with Razorpay Route. Here is how you can enable Razorpay Route on test mode.',
  },

  subscriptions: {
    formComponent: SubscriptionsForm,
    links: {
      docs: '',
      knowMore: '',
    },
    formText:
      'This is to help you get started with Razorpay Subscriptions. Here is how you can enable Razorpay Subscriptions on test mode.',
  },

  virtual_accounts: {
    formComponent: VirtualAccountsForm,
    links: {
      docs: '',
      knowMore: '',
    },
    formText: 'Some random text',
  },
};

export default FORM_TYPE;
