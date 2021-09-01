import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen } from 'test-utils';

import InfoTooltip from '../index';

describe('InfoTooltip', () => {
  test('show InfoTooltip when overlay is a text', () => {
    const App = () => (
      <InfoTooltip overlay="I am a tooltip text">
        <input data-testid="tooltipChild" placeholder="Click to view info tooltip" />
      </InfoTooltip>
    );
    render(<App />, {});
    screen.getByTestId('tooltipChild').focus();
    expect(screen.getByTestId('infoTooltip')).toBeInTheDocument();
    expect(screen.getByText('I am a tooltip text')).toBeInTheDocument();
  });

  test('show InfoTooltip when overlay is an object', () => {
    const App = () => (
      <InfoTooltip overlay={{ name: 'Ajay', accountNo: '23456' }}>
        <input data-testid="tooltipChild" placeholder="Click to view info tooltip" />
      </InfoTooltip>
    );
    render(<App />, {});
    screen.getByTestId('tooltipChild').focus();
    expect(screen.getByTestId('infoTooltip')).toBeInTheDocument();
    expect(screen.getByTestId('infoList').childNodes).toHaveLength(2);
  });
});
