// test-utils.js
import React, { ReactElement } from 'react';
import { render } from '@testing-library/react';
import { Router, Route } from 'react-router-dom';
// eslint-disable-next-line import/no-extraneous-dependencies
import { createMemoryHistory } from 'history';
import Wrapper from '../../components/Bootstrap/Wrapper';

const AllTheProviders: React.FC<{ children: ReactElement<any, any> | null }> = ({ children }) => {
  return (
    <Wrapper context={{ mode: 'test', org: { id: '123' }, user: { contact_name: 'prashant' } }}>
      <Router history={createMemoryHistory({ initialEntries: ['/'] })}>
        <Route path="/" component={() => children} />
      </Router>
    </Wrapper>
  );
};

// eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
const customRender = (ui, options) => render(ui, { wrapper: AllTheProviders, ...options });

// re-export everything
export * from '@testing-library/react';

// override render method
export { customRender as render };
