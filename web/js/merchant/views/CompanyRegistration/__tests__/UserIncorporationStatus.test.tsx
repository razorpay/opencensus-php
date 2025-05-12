import React from 'react';
import { render, screen } from 'test-utils';
import UserIncorporationStatus from '../components/UserIncorporationStatus';
import { TRACK_PROGRESS } from '../constant';
import * as analytics from '../analytics';
import type { StatusStepsType } from '../types';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';

// Mock child component
jest.mock('../components/StepsItem', () => ({ icon, title, status }: any) => (
  <div data-testid="steps-item">
    <span>{title}</span>
    <span>{status}</span>
  </div>
));

// Mock analytics
jest.spyOn(analytics, 'trackEventOnUserStatusPageView').mockImplementation(jest.fn());

describe('UserIncorporationStatus', () => {
  const mockSteps: StatusStepsType[] = [
    { icon: () => <div>Icon1</div>, title: 'Step 1', status: 'Completed' },
    { icon: () => <div>Icon2</div>, title: 'Step 2', status: 'Ongoing' },
  ];

  const getStepsMock = () => mockSteps;
  const renderComponent = () =>
    render(
      <BladeProvider themeTokens={bladeTheme}>
        <UserIncorporationStatus isSmallDevice={false} getSteps={getStepsMock} />
      </BladeProvider>,
    );
  test('renders heading, subtext and steps correctly', () => {
    renderComponent();

    // Check heading & subtext
    expect(screen.getByText(TRACK_PROGRESS)).toBeInTheDocument();

    // Check all steps
    mockSteps.forEach((step) => {
      expect(screen.getByText(step.title)).toBeInTheDocument();
      expect(screen.getByText(step.status)).toBeInTheDocument();
    });
  });

  test('calls analytics tracking on mount', () => {
    renderComponent();
    expect(analytics.trackEventOnUserStatusPageView).toHaveBeenCalled();
  });
});
