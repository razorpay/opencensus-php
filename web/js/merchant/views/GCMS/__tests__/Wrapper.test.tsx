import React from 'react';
import { render as rootRender, screen } from '@testing-library/react';
import { createMemoryHistory } from 'history';
import { Router } from 'react-router-dom';

import { SpiltzContextState } from 'common/splitz/types';
import GCMSWrapper from 'merchant/views/GCMS/shared/Wrapper';

const variantOn = { razorpay_gcms: { variables: { result: 'on' } } };
const variantOff = { razorpay_gcms: { variables: { result: 'off' } } };

const defaultAbExperiments = {};
let mockAbExperiments = defaultAbExperiments;
jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments } as unknown as SpiltzContextState),
}));

const TestComponent = () => {
  return <div>Test</div>;
};

describe('GCMS: Wrapper', () => {
  it('should render children if experiment is enabled', () => {
    mockAbExperiments = variantOn;
    const history = createMemoryHistory({
      initialEntries: ['/gcms/programs'],
    });
    history.push = jest.fn();
    rootRender(
      <Router location={history.location} navigator={history}>
        <GCMSWrapper>
          <TestComponent />
        </GCMSWrapper>
      </Router>,
    );
    expect(screen.getByText('Test')).toBeInTheDocument();
  });

  it('should not redirect to dashboard if experiment is not enabled', () => {
    mockAbExperiments = variantOff;

    const history = createMemoryHistory({
      initialEntries: ['/gcms/programs'],
    });
    history.push = jest.fn();
    rootRender(
      <Router location={history.location} navigator={history}>
        <GCMSWrapper>
          <TestComponent />
        </GCMSWrapper>
      </Router>,
    );
    expect(history.location.pathname).toBe('/dashboard');
  });
});
