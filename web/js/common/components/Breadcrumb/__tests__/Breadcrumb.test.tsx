import React from 'react';
import { render, screen } from 'test-utils';
import Breadcrumb from 'common/components/Breadcrumb';

describe('Breadcrumb', () => {
  test('should render Breadcrumb', () => {
    render(
      <Breadcrumb
        items={[
          {
            label: 'Account & Settings',
            link: '/account-settings',
          },
          {
            label: 'Pofile',
            link: '/profile',
          },
        ]}
      />,
    );
    expect(screen.getByText('Account & Settings')).toBeInTheDocument();
    expect(screen.getByText('Pofile')).toBeInTheDocument();
  });
});
