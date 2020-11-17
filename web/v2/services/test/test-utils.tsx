// test-utils.js
import React, { ReactNode } from 'react';
import { render } from '@testing-library/react';
import Wrapper from '../../components/Bootstrap/Wrapper';

const AllTheProviders: React.FC<{ children: ReactNode }> = ({ children }) => {
  return <Wrapper context={{ mode: 'test', org: { id: '123' } }}>{children}</Wrapper>;
};

// eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
const customRender = (ui, options) => render(ui, { wrapper: AllTheProviders, ...options });

// re-export everything
export * from '@testing-library/react';

// override render method
export { customRender as render };
