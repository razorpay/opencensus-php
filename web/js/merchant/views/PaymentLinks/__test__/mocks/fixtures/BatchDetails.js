import { render } from 'test-utils';

import PaymentLinksBatchDetailsContainer from 'merchant/views/PaymentLinks/BatchUpload/Details';

export function renderApp({ props = {} }) {
  return render(<PaymentLinksBatchDetailsContainer {...props} />);
}
