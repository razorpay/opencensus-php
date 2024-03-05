import React from 'react';
import NoSearchResult from 'self-serve/src/App/Transactions/v2/common/components/NoSearchResult';
import { render } from 'apps/self-serve/src/services/test/test-utils';

export const renderApp = ({ page }) => render(<NoSearchResult page={page} />);
