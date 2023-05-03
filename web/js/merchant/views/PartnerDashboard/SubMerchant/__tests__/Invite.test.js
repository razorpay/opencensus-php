import React from 'react';
import { render, screen } from 'common/services/test/test-utils';
import Invite from 'merchant/views/PartnerDashboard/SubMerchant/Invite';

// TODO: only basic render test added, other tests can be added later.

const defaultProps = {
  handleSubmit: jest.fn(),
  closeModal: jest.fn(),
  onClose: jest.fn(),
};

describe('Invite', () => {
  test('should render invite', () => {
    render(<Invite {...defaultProps} />);
    expect(
      screen.getByText(
        'By inviting the merchant to sign up on the dashboard, you both can manage the account.',
      ),
    ).toBeVisible();
  });
});
