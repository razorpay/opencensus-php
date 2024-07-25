import React from 'react';
import OnboardingHeader from '../OnboardingHeader';
import { render, screen, userEvent } from 'apps/pos/src/services/test/test-utils';

export const mockNavigate = jest.fn();
jest.mock('react-router-dom', () => ({
  ...jest.requireActual('react-router-dom'),
  useNavigate: () => mockNavigate,
}));

describe('<OnboardingHeader/>', () => {
  test('should render onboarding header on screen', () => {
    const props = {
      pageLabel: 'Some Random Step',
      title: 'Some Random Title',
      description: 'Some Random Description',
    };

    render(<OnboardingHeader {...props} />);
    expect(screen.getByText('Some Random Step')).toBeInTheDocument();
    expect(screen.getByText('Some Random Title')).toBeInTheDocument();
    expect(screen.getByText('Some Random Description')).toBeInTheDocument();
  });

  test('should trigger navigate when clicked on back button', async () => {
    const props = {
      isBackButtonVisible: true,
      footer: <div>Some Random Footer</div>,
    };

    render(<OnboardingHeader {...props} />);
    expect(screen.getByText('Some Random Footer')).toBeInTheDocument();
    await userEvent.click(screen.getByLabelText('header-back-btn'));
    expect(mockNavigate).toHaveBeenCalledWith(-1);
  });
});
