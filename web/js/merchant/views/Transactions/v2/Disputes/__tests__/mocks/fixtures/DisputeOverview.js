import React from 'react';
import { render } from 'test-utils';

import DisputeOverview from 'merchant/views/Transactions/v2/Disputes/components/DisputeOverview';

const defaultProps = {
  mode: 'test',
};

export const renderApp = () => render(<DisputeOverview {...defaultProps} />);
