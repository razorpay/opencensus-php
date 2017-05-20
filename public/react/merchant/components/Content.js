import { Route } from 'react-router-dom';

import Transactions from 'merchant/containers/Transactions';
import Settlements from 'merchant/containers/Settlements/List';
import PaymentLinks from 'merchant/containers/Invoices/PaymentLinks';
import InvoicingContainer from 'merchant/containers/Invoicing';
import InvoicesNew from 'merchant/containers/Invoices/New';
import Customers from 'merchant/containers/Customers/List';
import Accounts from 'merchant/containers/Accounts/List';
import Reports from 'merchant/containers/Reports';
import Referrals from 'merchant/containers/Referrals/List';
import TeamManagement from 'merchant/containers/Team';

import MyAccount from 'merchant/containers/MyAccount';
import Settings from 'merchant/containers/Settings';

export default () => {
  return (
    <main class="main-content">
      {/* Transaction routes */}
      <Route path="/app/payments" component={Transactions} />
      <Route path="/app/refunds" component={Transactions} />
      <Route path="/app/orders" component={Transactions} />

      <Route path="/app/settlements" component={Settlements} />

      <Route path="/app/invoices" exact component={InvoicingContainer} />
      <Route path="/app/invoices/:id(inv_.+)" component={InvoicesNew} />
      <Route path="/app/invoices/new" component={InvoicesNew} />
      <Route path="/app/items" component={InvoicingContainer} />

      <Route path="/app/paymentlinks" exact component={PaymentLinks} />

      <Route path="/app/customers" component={Customers} />

      <Route path="/app/accounts" component={Accounts} />

      <Route path="/app/reports" component={Reports} />
      <Route path="/app/referrals" component={Referrals} />
      <Route path="/app/team" component={TeamManagement} />

      <Route path="/app/profile" component={MyAccount} />
      <Route path="/app/activation" component={MyAccount} />
      <Route path="/app/addfunds" component={MyAccount} />
      <Route path="/app/credits" component={MyAccount} />

      <Route path="/app/config" component={Settings} />
      <Route path="/app/keys" component={Settings} />
      <Route path="/app/webhooks" component={Settings} />
    </main>
  );
};
