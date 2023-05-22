import ProfileInfo from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/sections/Profile/views/v2/components/ProfileInfo';
import React from 'react';
import { render, screen } from 'test-utils';
import { titleCase } from 'common/utils/rzp-utils';
import { getInitials } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/sections/Profile/views/v2/utils';

const defaultProps = {
  userRole: 'Owner',
  user: {
    id: 'LLlOXKFCKOM39x',
    logo_url: 'https://www.s3.profile/',
    user: {
      name: 'test merchant',
    },
  },
};

const renderApp = (props: Record<string, any>) =>
  render(<ProfileInfo {...defaultProps} {...props} />);

describe('ProfileInfo', () => {
  test('should render user name and user role', () => {
    renderApp({});
    expect(screen.getByText(titleCase(defaultProps.user.user.name))).toBeInTheDocument();
    expect(screen.getByText(defaultProps.userRole)).toBeInTheDocument();
  });

  test('should render merchant id', () => {
    renderApp({
      user: {
        id: 'LLlOXKFCKOM39x',
        logo_url: null,
        user: {
          name: '',
        },
      },
    });
    expect(screen.getByText('Merchant ID')).toBeInTheDocument();
    expect(screen.getByText(defaultProps.user.id)).toBeInTheDocument();
  });

  test('should render name initials if logo url not present', () => {
    renderApp({
      user: {
        ...defaultProps.user,
        logo_url: null,
      },
    });
    const initials = getInitials(defaultProps.user.user.name);
    expect(screen.getByText(initials)).toBeInTheDocument();
  });
});
