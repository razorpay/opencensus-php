import { Route } from 'react-router-dom';

import Transactions from 'merchant/containers/Transactions';
import Settlements from 'merchant/containers/Settlements/List';
import InvoicingContainer from 'merchant/containers/Invoicing';
import Reports from 'merchant/containers/Reports';
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
      <Route path="/app/batch-refunds" component={Transactions} />

      <Route path="/app/settlements" component={Settlements} />

      <Route path="/app/invoices" component={InvoicingContainer} />
      <Route path="/app/customers" component={InvoicingContainer} />
      <Route path="/app/items" component={InvoicingContainer} />

      <Route path="/app/reports" component={Reports} />
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
