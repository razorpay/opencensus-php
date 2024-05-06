import React from 'react';
import Ftux from 'apps/self-serve/src/App/Transactions/v2/common/components/Ftux';
import { render } from 'apps/self-serve/src/services/test/test-utils';

export const renderApp = ({ page }) => render(<Ftux page={page} />);
