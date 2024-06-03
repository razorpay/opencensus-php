import SuccessPopup from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/SuccessPopup';
import { BASE_PAYMENT_LINK_URL } from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/constants';
import * as services from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/services';
import { render, screen, userEvent } from 'test-utils';

const defaultProps = {
  isOpen: true,
  account: 'GBP',
};

const publicPaymentLink = 'dummytag';

const renderApp = (props = {}) => {
  return render(<SuccessPopup {...defaultProps} {...props} />, {
    initialState: {
      b2bExportsAccounts: {
        publicPaymentLink,
      },
    },
  });
};

describe('Tests for SuccessPopup - SEPA bank transfer', () => {
  test('Should render without breaking', () => {
    renderApp();

    expect(
      screen.getByText(`Request for ${defaultProps.account} Currency Bank Account`),
    ).toBeInTheDocument();
  });

  test('Should show Copy link button by default', async () => {
    renderApp();

    expect(screen.getByRole('button', { name: 'Copy link' })).toBeInTheDocument();
    await userEvent.click(screen.getByRole('button', { name: 'Copy link' }));
  });

  test('Should show input in disabled state with public link on init', () => {
    renderApp();

    expect(screen.getByRole('textbox')).toBeInTheDocument();
    expect(screen.getByRole('textbox')).toBeDisabled();
  });

  test('Should show Edit link and Copy link if shouldAllowEdit is true', () => {
    renderApp({ shouldAllowEdit: true });

    expect(screen.getByRole('button', { name: 'Edit link' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Copy link' })).toBeInTheDocument();

    expect(
      screen.getByText('You may edit the link name so you and your customer can remember the link'),
    ).toBeInTheDocument();
  });

  test('Should allow to edit the link when edit link is clicked', async () => {
    const updatePublicPaymentLinkMock = jest.spyOn(services, 'updatePublicPaymentLink');
    renderApp({ shouldAllowEdit: true });

    await userEvent.click(screen.getByRole('button', { name: 'Edit link' }));

    expect(screen.getByRole('textbox')).not.toBeDisabled();
    expect(screen.getByRole('button', { name: 'Cancel' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Save changes' })).toBeInTheDocument();

    await userEvent.type(screen.getByRole('textbox'), 'text');
    await userEvent.click(screen.getByRole('button', { name: 'Save changes' }));

    expect(updatePublicPaymentLinkMock).toHaveBeenCalledWith(`${publicPaymentLink}text`);
  });

  test('Should default back to previous link on cancel click', async () => {
    renderApp({ shouldAllowEdit: true });
    const textBox = screen.getByRole('textbox');

    await userEvent.click(screen.getByRole('button', { name: 'Edit link' }));
    await userEvent.type(textBox, 'text');

    await userEvent.click(screen.getByRole('button', { name: 'Cancel' }));
    expect(textBox.value).toBe(`${BASE_PAYMENT_LINK_URL}${publicPaymentLink}`);
  });
});
