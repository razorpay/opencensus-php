import React from 'react';

import { renderWithSuspense, screen } from 'test-utils';

import AnimatedBarGraph from '../AnimatedBarGraph';
import { BAR_GRAPH_VARIANTS } from '../constants';

const renderApp = () =>
  renderWithSuspense(
    <AnimatedBarGraph
      variant={BAR_GRAPH_VARIANTS.NEUTRAL_POSITIVE_INCREASE}
      barLabels={['Label Left', 'Label Right']}
      graphLabel="Product Name"
      graphValue="+30%"
    />,
  );

describe('Widgets -> AnimatedBarGraph', () => {
  it('should render the AnimatedBarGraph component', () => {
    renderApp();
    expect(screen.getByTestId('animated-bar-graph')).toBeInTheDocument();
  });

  it('should show the correct graph label', () => {
    renderApp();
    expect(screen.getByText('Product Name')).toBeInTheDocument();
  });

  it('should show the correct bar labels', () => {
    renderApp();
    expect(screen.getByText('Label Left')).toBeInTheDocument();
    expect(screen.getByText('Label Right')).toBeInTheDocument();
  });

  it('should show the correct graph value labels', () => {
    renderApp();
    expect(screen.getByText('+30%')).toBeInTheDocument();
  });
});
