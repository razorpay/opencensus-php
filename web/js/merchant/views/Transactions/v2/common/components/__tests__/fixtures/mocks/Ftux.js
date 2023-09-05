import React from 'react';
import Ftux from 'merchant/views/Transactions/v2/common/components/Ftux';
import { render } from 'test-utils';

export const renderApp = ({ page }) => render(<Ftux page={page} />);
