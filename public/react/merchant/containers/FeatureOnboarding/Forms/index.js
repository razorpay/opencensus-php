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
    formText: '',
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
