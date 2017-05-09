import { Route } from 'react-router-dom';

import Payments from 'merchant/containers/Payments/List';
import Transactions from 'merchant/containers/Transactions';
import Refunds from 'merchant/containers/Refunds/List';
import Orders from 'merchant/containers/Orders/List';
import Settlements from 'merchant/containers/Settlements/List';
import Invoices from 'merchant/containers/Invoices/List';
import AddFunds from 'merchant/containers/AddFunds';
import Reports from 'merchant/containers/Reports';
import TeamManagement from 'merchant/containers/Team';
import Credits from 'merchant/containers/Credits/List';
import Keys from 'merchant/containers/Keys/List';
import Activation from 'merchant/containers/Activation';
import Webhooks from 'merchant/containers/Webhooks/List';
import Configuration from 'merchant/containers/Configuration';

export default () => {
  return (
    <main class="main-content">

      {/* Transaction routes */}
      <Route path="/payments" component={Transactions} />
      <Route path="/refunds" component={Transactions} />
      <Route path="/orders" component={Transactions} />
      <Route path="/batch-refunds" component={Transactions} />

      <Route path="/settlements" component={Settlements} />
      <Route path="/invoices" component={Invoices} />
      <Route path="/reports" component={Reports} />
      <Route path="/team" component={TeamManagement} />
      <Route path="/credits" component={Credits} />
      <Route path="/keys" component={Keys} />
      <Route path="/activation" component={Activation} />
      <Route path="/webhooks" component={Webhooks} />
      <Route path="/settings" component={Configuration} />
    </main>
  );
};
