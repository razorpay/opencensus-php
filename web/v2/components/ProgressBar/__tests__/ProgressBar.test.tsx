import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import ProgressBar from '../ProgressSteps';
import { render, screen } from 'test-utils';

describe('Progress Bar', () => {
  test('with 5 steps', () => {
    const App = () => <ProgressBar currentStep={3} totalSteps={5} />;
    render(<App />, {});
    expect(screen.getByTestId('progressSteps')).toBeInTheDocument();
    expect(screen.getByTestId('progressSteps').childNodes).toHaveLength(5);
  });
  test('with 3 steps and level at 2', () => {
    const App = () => <ProgressBar currentStep={3} totalSteps={3} />;
    render(<App />, {});
    expect(screen.getByTestId('progressSteps')).toBeInTheDocument();
    expect(screen.getByTestId('progressSteps').childNodes).toHaveLength(3);
  });
});
