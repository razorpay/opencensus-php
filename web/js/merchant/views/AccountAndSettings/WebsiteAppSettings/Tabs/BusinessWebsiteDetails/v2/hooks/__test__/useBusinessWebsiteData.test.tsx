import React, { useState } from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';

import {
  BusinessWebsiteAutomationContext,
  BusinessWebsiteAutomationContextType,
} from '../../context';
import { WebsiteSubmitModalSteps } from '../../types';
import useBusinessWebsiteData from '../useBusinessWebsiteData';

const TestComponent = () => {
  const { currentStep, setCurrentStep } = useBusinessWebsiteData();
  return (
    <div>
      <span data-testid="currentStep">{currentStep}</span>
      <button onClick={() => setCurrentStep(WebsiteSubmitModalSteps.ADD_MISSING_POLICY_PAGES)}>
        Fix Policy Pages
      </button>
    </div>
  );
};

const TestComponentWithContext = () => {
  const [currentStep, setCurrentStep] = useState(WebsiteSubmitModalSteps.ADD_MAIN_PAGE);

  return (
    <BusinessWebsiteAutomationContext.Provider
      value={
        {
          currentStep,
          setCurrentStep,
        } as BusinessWebsiteAutomationContextType
      }
    >
      <TestComponent />
    </BusinessWebsiteAutomationContext.Provider>
  );
};

describe('Business website automation - useBusinessWebsiteData', () => {
  it('should return currentStep and setCurrentStep', async () => {
    render(<TestComponentWithContext />);
    expect(screen.getByTestId('currentStep')).toHaveTextContent(
      WebsiteSubmitModalSteps.ADD_MAIN_PAGE,
    );
    await userEvent.click(screen.getByRole('button'));
    await waitFor(() => {
      expect(screen.getByTestId('currentStep')).toHaveTextContent(
        WebsiteSubmitModalSteps.ADD_MISSING_POLICY_PAGES,
      );
    });
  });
});
