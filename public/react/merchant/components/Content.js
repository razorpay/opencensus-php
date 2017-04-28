import { Route } from 'react-router-dom';

import Orders from 'merchant/containers/Orders/List';
import Refunds from 'merchant/containers/Refunds/List';

export default () => {
  return (
    <main class="main-content">
      <Route path="/orders" component={Orders} />
      <Route path="/refunds" component={Refunds} />
    </main>
  );
};
