import '@testing-library/jest-dom/extend-expect';
import ProfilePhoto from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/components/ProfilePhoto';
import React from 'react';
import { render, screen } from 'test-utils';

describe('ProfilePhoto', () => {
  const renderApp = (props) => render(<ProfilePhoto {...props} />);

  test('should render profile photo if image url is present', () => {
    const imageUrl = 'https://cdn.razorpay.com/logo_invert.svg';
    renderApp({
      imageUrl,
    });
    const profileImage = screen.getByRole('img');
    expect(profileImage).toBeInTheDocument();
    expect(profileImage).toHaveAttribute('src', imageUrl);
  });

  test('should render profile default icon if image is not present', () => {
    renderApp({});
    const profileIcon = screen.getByText((_, element) => element?.tagName.toLowerCase() === 'i');
    expect(profileIcon).toBeInTheDocument();
    expect(profileIcon).toHaveAttribute('class', 'i i-profile');
  });
});
