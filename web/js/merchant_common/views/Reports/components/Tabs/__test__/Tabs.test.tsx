import React from 'react';
import { Tabs } from 'merchant_common/views/Reports/components';
import { render, screen } from 'test-utils';

describe('Tabs', () => {
  test('should render component without error', () => {
    render(
      <Tabs
        basePath=""
        tabs={[
          {
            component: <p>TAB1</p>,
            label: 'T1',
            to: '/T1',
            exact: true,
          },
          {
            component: <p>TAB2</p>,
            label: 'T2',
            to: '/T2',
            exact: true,
          },
        ]}
      />,
      {
        location: {
          pathname: '/T1',
        },
      },
    );
    expect(screen.getByLabelText('T1')).toBeInTheDocument();
    expect(screen.getByLabelText('T2')).toBeInTheDocument();
  });
});
