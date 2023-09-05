import React from 'react';
import { render } from 'test-utils';
import Title from 'merchant/views/Transactions/v2/common/components/Title';

export const renderApp = ({ children }) => render(<Title children={children} />);
