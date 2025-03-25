import React from 'react';
import { render, screen } from 'test-utils';
import LandingPage from 'merchant/views/CustomerTrust/components/LandingPage';

describe('Landing page', () => {
  test('should show all data', () => {
    render(
      <LandingPage
        onboardingStatus={null}
        setOnboardingStatus={jest.fn()}
        showNotification={jest.fn()}
      />,
    );
    expect(screen.getByText('Razorpay Buyer Protection')).toBeInTheDocument();
    expect(
      screen.getByText(
        'Build trust instantly with the Money-Back Promise widget on your product page.',
      ),
    ).toBeInTheDocument();

    expect(screen.getByText('Get 30% more orders')).toBeInTheDocument();
    expect(
      screen.getByText('Enhance buyer trust with reliable purchase protection'),
    ).toBeInTheDocument();
    expect(screen.getByText('Increase Average Order Value')).toBeInTheDocument();
    expect(
      screen.getByText('Encourage higher spending by building buyer confidence'),
    ).toBeInTheDocument();
    expect(screen.getByText('Boost Prepaid Orders')).toBeInTheDocument();
    expect(
      screen.getByText('Encourage upfront payments with trusted buyer assurance'),
    ).toBeInTheDocument();

    expect(screen.getByText('Learn more')).toBeInTheDocument();
  });

  test('should render the correct button if form not submitted already', () => {
    render(
      <LandingPage
        onboardingStatus={null}
        setOnboardingStatus={jest.fn()}
        showNotification={jest.fn()}
      />,
    );
    expect(screen.getByText('I am Interested')).toBeInTheDocument();
  });

  test("should render the correct button if 'I am interested' was clicked but form was not submitted", () => {
    render(
      <LandingPage
        onboardingStatus="interested"
        setOnboardingStatus={jest.fn()}
        showNotification={jest.fn()}
      />,
    );
    expect(screen.getByText('I am Interested')).toBeInTheDocument();
  });

  test('should render the correct button if form was submitted already', () => {
    render(
      <LandingPage
        onboardingStatus="completed"
        setOnboardingStatus={jest.fn()}
        showNotification={jest.fn()}
      />,
    );
    expect(screen.getByText('Interest Confirmed')).toBeInTheDocument();
  });
});
