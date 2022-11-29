import React from 'react';
import Step from 'merchant/views/ApiKeysAndPlugins/KeysAndPlugins/components/Step';
import { render } from 'test-utils';

describe('API Keys & Plugins - Step', () => {
  test('should render step number and children correctly', () => {
    const text = 'Hey! Can you see me?';
    const stepNumber = 1024;
    const { getByText } = render(
      <Step borderBottom={false} step={stepNumber}>
        {text}
      </Step>,
    );
    expect(getByText(text)).toBeInTheDocument();
    expect(getByText(stepNumber)).toBeInTheDocument();
  });

  test('should render Step with border', () => {
    const text = 'Can you see me now?';
    const { getByText } = render(
      <Step borderBottom={true} step={1}>
        {text}
      </Step>,
    );
    expect(getByText(text)).toBeInTheDocument();
  });
});
