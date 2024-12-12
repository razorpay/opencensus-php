import React from 'react';
import { render, screen } from 'test-utils';

import { getOnBoardingDataFromLocalState } from 'merchant/components/OnBoarding';
import { Pricing } from 'merchant/views/Optimizer/OnBoarding/components/Pricing';
import { SubmitSuccess } from 'merchant/views/Optimizer/OnBoarding/components/SubmitSuccess';

import { Info } from 'merchant/views/Optimizer/OnBoarding/components/Info';

jest.mock('merchant/components/OnBoarding', () => ({
  getOnBoardingDataFromLocalState: jest.fn(),
  setOnBoardingDataInLocalState: jest.fn(),
}));

jest.mock('merchant/views/Optimizer/OnBoarding/components/Pricing', () => ({
  Pricing: jest.fn(() => <div>Pricing Component</div>),
}));

jest.mock('merchant/views/Optimizer/OnBoarding/components/SubmitSuccess', () => ({
  SubmitSuccess: jest.fn(() => <div>SubmitSuccess Component</div>),
}));

const renderComponent = (props) => {
  return render(<Info {...props} />);
};

describe('Optimizer OnBoarding - Info', () => {
  afterAll(() => {
    jest.resetAllMocks();
  });

  test('Should render without errors', () => {
    expect(renderComponent()).toBeDefined();
  });

  test('should render pricing plan details', () => {
    getOnBoardingDataFromLocalState.mockReturnValue({
      hasSubmittedSuccessfully: false,
    });
    renderComponent();
    expect(Pricing).toHaveBeenCalledTimes(1);
    expect(screen.getByText('Pricing Component')).toBeInTheDocument();
  });

  test('should render submit success comp', () => {
    getOnBoardingDataFromLocalState.mockReturnValue({
      hasSubmittedSuccessfully: true,
    });
    renderComponent({ successfullySubmitted: true });
    expect(SubmitSuccess).toHaveBeenCalledTimes(1);
  });
});
