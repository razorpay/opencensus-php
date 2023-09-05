import React from 'react';
import NoSearchResult from 'merchant/views/Transactions/v2/common/components/NoSearchResult';
import { render } from 'test-utils';

export const renderApp = ({ page }) => render(<NoSearchResult page={page} />);
