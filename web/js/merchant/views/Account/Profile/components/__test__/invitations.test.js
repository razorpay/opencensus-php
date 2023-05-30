import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, userEvent } from 'test-utils';
import Invitations from 'merchant/views/Account/Profile/components/Invitations';

describe('InvitationsPanel', () => {
  const invitations = [
    {
      merchant_name: 'Merchant 1',
      id: 1,
    },
    {
      merchant_name: 'Merchant 2',
      id: 2,
    },
  ];

  const onAcceptClick = jest.fn();
  const onRejectClick = jest.fn();

  const renderApp = () => {
    return render(
      <Invitations
        invitations={invitations}
        onAcceptClick={onAcceptClick}
        onRejectClick={onRejectClick}
      />,
    );
  };

  it('renders without crashing', () => {
    renderApp();
  });

  it('renders the correct number of invitations', () => {
    const { getAllByTestId } = renderApp();
    expect(getAllByTestId('invitation-merchant-name')).toHaveLength(invitations.length);
  });

  it('calls onAcceptClick when accept button is clicked', async () => {
    const { getAllByText } = renderApp();
    await userEvent.click(getAllByText('Accept')[0]);
    expect(onAcceptClick).toHaveBeenCalledWith(invitations[0]);
  });

  it('calls onRejectClick when reject button is clicked', async () => {
    const { getAllByText } = renderApp();
    await userEvent.click(getAllByText('Reject')[0]);
    expect(onRejectClick).toHaveBeenCalledWith(invitations[0]);
  });
});
