import React from 'react';
import { Table } from 'merchant_common/views/Reports/components';
import { TableText } from 'merchant_common/views/Reports/components/Table/Components/TableText';
import { render, screen, userEvent } from 'test-utils';

const onPageChange = jest.fn();

describe('Tabs', () => {
  test('should render component without error', async () => {
    render(
      <Table
        currentPage={1}
        rows={[
          {
            name: 'dashboard',
            email: 'd@gmail.com',
          },
          {
            name: 'admin-dashboard',
            email: 'ad@gmail.com',
          },
        ]}
        template={{
          cells: [
            {
              render: ({ name }) => {
                return <TableText>{name}</TableText>;
              },
            },
            {
              render: ({ email }) => {
                return <TableText>{email}</TableText>;
              },
            },
          ],
          headers: ['Name of Dashboard', 'Emails'],
        }}
        totalRows={200}
        onPageChange={onPageChange}
      />,
    );

    expect(screen.getByLabelText(`Name of Dashboard`)).toBeInTheDocument();
    expect(screen.getByLabelText(`Emails`)).toBeInTheDocument();
    expect(screen.getByText('dashboard')).toBeInTheDocument();
    expect(screen.getByText('ad@gmail.com')).toBeInTheDocument();
    expect(screen.getByLabelText(`Page no is ${1}`)).toBeInTheDocument();
    expect(screen.getByLabelText(`Page no is ${2}`)).toBeInTheDocument();
    await userEvent.click(screen.getByLabelText(`Page no is ${2}`));
    expect(onPageChange).toHaveBeenCalled();
  });
});
