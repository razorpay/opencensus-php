import React from 'react';

import { render, screen, fireEvent } from 'test-utils';
import FeatureToggle from 'merchant/views/Settings/Configuration/components/Configuration/FeatureToggle';

describe('FeatureToggle', () => {
  const toggleHandler = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders with the correct title and subtitle', () => {
    render(
      <FeatureToggle
        feature="testFeature"
        isChecked={false}
        title="Test Feature"
        subTitle="This is a test feature"
        toggleHandler={toggleHandler}
      />,
    );

    expect(screen.getByText('Test Feature')).toBeInTheDocument();
    expect(screen.getByText('This is a test feature')).toBeInTheDocument();
  });

  it('renders the switch with the correct accessibility label', () => {
    render(
      <FeatureToggle
        feature="testFeature"
        isChecked={false}
        title="Test Feature"
        subTitle="This is a test feature"
        toggleHandler={toggleHandler}
      />,
    );

    const switchElement = screen.getByLabelText('enable-testFeature');
    expect(switchElement).toBeInTheDocument();
  });

  it('calls toggleHandler when the switch is toggled to checked', () => {
    render(
      <FeatureToggle
        feature="testFeature"
        isChecked={false}
        title="Test Feature"
        subTitle="This is a test feature"
        toggleHandler={toggleHandler}
      />,
    );

    const switchElement = screen.getByLabelText('enable-testFeature');
    fireEvent.click(switchElement);

    expect(toggleHandler).toHaveBeenCalledWith(true);
  });

  it('calls toggleHandler when the switch is toggled to unchecked', () => {
    render(
      <FeatureToggle
        feature="testFeature"
        isChecked={true}
        title="Test Feature"
        subTitle="This is a test feature"
        toggleHandler={toggleHandler}
      />,
    );

    const switchElement = screen.getByLabelText('enable-testFeature');
    fireEvent.click(switchElement);

    expect(toggleHandler).toHaveBeenCalledWith(false);
  });

  it('handles the switch being checked', () => {
    render(
      <FeatureToggle
        feature="testFeature"
        isChecked={true}
        title="Test Feature"
        subTitle="This is a test feature"
        toggleHandler={toggleHandler}
      />,
    );

    const switchElement = screen.getByLabelText('enable-testFeature');
    expect(switchElement).toBeChecked();
  });

  it('handles the switch being unchecked', () => {
    render(
      <FeatureToggle
        feature="testFeature"
        isChecked={false}
        title="Test Feature"
        subTitle="This is a test feature"
        toggleHandler={toggleHandler}
      />,
    );

    const switchElement = screen.getByLabelText('enable-testFeature');
    expect(switchElement).not.toBeChecked();
  });
});
