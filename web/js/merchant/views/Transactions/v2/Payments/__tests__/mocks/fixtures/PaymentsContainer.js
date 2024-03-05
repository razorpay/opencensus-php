import { render } from 'test-utils';

import PaymentsContainer from 'merchant/views/Transactions/v2/Payments/components/PaymentsContainer';

export const renderApp = (props = {}, path = '/', initialEntries = ['/']) => {
  return render(<PaymentsContainer location={{ pathname: '/' }} {...props} />, {
    renderViaRouteGuard: false,
    path,
    initialEntries,
  });
};
