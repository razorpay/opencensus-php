import ProfileDropdown from 'merchant/components/HeaderNav/ProfileDropdownV2';
import User from 'merchant/models/User';
import store from 'merchant/store';
import { render, screen, userEvent, waitFor } from 'test-utils';
import { user } from './fixtures/mocks';

const updateStore = (user, org = {}) => {
  const updatedStore = store.getState();
  updatedStore.session.user = new User({ ...updatedStore.session.user, ...user });
  updatedStore.session.org = { ...updatedStore.session.org, ...org };
  return updatedStore;
};

const mockOnSwitchMode = jest.fn();
const mockOnLogout = jest.fn();
const mockOpenSwitchMerchantModal = jest.fn();

const defaultProps = {
  user,
  mode: 'live',
  onSwitchMode: mockOnSwitchMode,
  onLogout: mockOnLogout,
  openSwitchMerchantModal: mockOpenSwitchMerchantModal,
  isRTBEnabled: false,
  trustedBadgeTooltipInfo: 'XYZ',
};

const renderApp = ({ props = {}, state = updateStore() } = {}) =>
  render(<ProfileDropdown {...defaultProps} {...props} />, { initialState: state });

describe('test for ProfileDropdownV2 component', () => {
  afterEach(() => {
    jest.clearAllMocks();
  });

  test('should show merchant name and id', () => {
    renderApp();
    expect(screen.getByText(user.name)).toBeInTheDocument();
    expect(screen.getByText(user.id)).toBeInTheDocument();
  });

  test('should show user name and role', () => {
    renderApp();
    expect(screen.getByText(user.user.name)).toBeInTheDocument();
    expect(
      screen.getByText(user.userRole, {
        exact: false,
      }),
    ).toBeInTheDocument();
  });

  test('should show rzp trusted badge', () => {
    renderApp({
      props: {
        isRTBEnabled: true,
      },
    });
    expect(screen.getByTestId('trusted-badge')).toBeInTheDocument();
  });

  test('should show logout button', async () => {
    renderApp();
    const logoutBtn = screen.getByText('Log out');
    expect(logoutBtn).toBeInTheDocument();
    await userEvent.click(logoutBtn);
    await waitFor(() => {
      expect(mockOnLogout).toHaveBeenCalled();
    });
  });

  test('should show switch merchant CTA', async () => {
    renderApp({
      props: {
        ...defaultProps,
        user: {
          ...user,
          merchants: ['JYYN1SC4iU0697', 'JYYN1SC4iU0697'],
        },
      },
    });

    const switchMerchantBtn = screen.getByText('Switch Merchant');
    expect(switchMerchantBtn).toBeInTheDocument();
    await userEvent.click(switchMerchantBtn);
    await waitFor(() => {
      expect(mockOpenSwitchMerchantModal).toHaveBeenCalled();
    });
  });

  test('should show go to rzpx CTA', () => {
    window.open = jest.fn();
    renderApp({
      props: {
        ...defaultProps,
        user: {
          ...user,
          isRazorxAnnouncementEnabled: true,
        },
      },
    });

    const gotoRzpXBtn = screen.getByText('Go to RazorpayX');
    expect(gotoRzpXBtn).toBeInTheDocument();
  });
});
