import React from 'react';
import Title from 'self-serve/src/App/Transactions/v2/common/components/Title';
import { render } from 'apps/self-serve/src/services/test/test-utils';

export const renderApp = ({ children }) => render(<Title children={children} />);
