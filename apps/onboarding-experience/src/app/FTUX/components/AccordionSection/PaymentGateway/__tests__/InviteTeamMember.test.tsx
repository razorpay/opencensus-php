import React from 'react';
import { screen, fireEvent, act } from '@testing-library/react';
import renderWithWrappers from 'apps/onboarding-experience/src/services/test/renderWithWrappers';
import InviteTeamMember from '../InviteTeamMember';
import { useMerchantContext } from '@FTUX/context/MerchantContext';
import { INVITE_TEAM_MEMBER_VIDEO_URL } from '@FTUX/constants/accordion';

// Mock dependencies
jest.mock('@FTUX/context/MerchantContext', () => ({
  useMerchantContext: jest.fn(),
}));

// Mock InviteNewMemberModal component
jest.mock('@federated/dashboards/payments/components/NewMemberInvitationModal', () => ({
  __esModule: true,
  default: jest.fn().mockImplementation(({ isOpen, onSuccess, onDismiss }) => (
    <div data-testid="invite-member-modal" style={{ display: isOpen ? 'block' : 'none' }}>
      <button onClick={onSuccess} data-testid="invite-success-button">
        Invite Success
      </button>
      <button onClick={onDismiss} data-testid="cancel-invite-button">
        Cancel
      </button>
    </div>
  )),
}));

describe('InviteTeamMember Component', () => {
  const mockInitiateTwoFaAuth = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();

    // Default mock implementation
    (useMerchantContext as jest.Mock).mockReturnValue({
      initiateTwoFaAuth: mockInitiateTwoFaAuth,
    });
  });

  test('renders with correct text and buttons', () => {
    renderWithWrappers(<InviteTeamMember />);

    // Check that the component renders with the correct text
    expect(
      screen.getByText('This is a technical step and you may need some help to integrate keys.'),
    ).toBeInTheDocument();

    // Check that the video link is present
    const videoLink = screen.getByText('Watch a set up video');
    expect(videoLink).toBeInTheDocument();
    expect(videoLink.closest('a')).toHaveAttribute('href', INVITE_TEAM_MEMBER_VIDEO_URL);

    // Check that the invite button is present
    const inviteButton = screen.getByText('Invite your developer as an admin');
    expect(inviteButton).toBeInTheDocument();
  });

  test('shows loading spinner when invite button is clicked', async () => {
    // Set up the mock to not resolve immediately
    mockInitiateTwoFaAuth.mockImplementation(() => new Promise(() => {}));

    renderWithWrappers(<InviteTeamMember />);

    const inviteButton = screen.getByText('Invite your developer as an admin');

    await act(async () => {
      fireEvent.click(inviteButton);
    });

    // Check that the spinner is displayed
    expect(screen.getByTestId('loadingInviteTeamMember')).toBeInTheDocument();
  });

  test('opens invitation modal when 2FA succeeds', async () => {
    mockInitiateTwoFaAuth.mockResolvedValue(true);

    renderWithWrappers(<InviteTeamMember />);

    const inviteButton = screen.getByText('Invite your developer as an admin');

    await act(async () => {
      fireEvent.click(inviteButton);
    });

    // Check that the modal is displayed
    expect(screen.getByTestId('invite-member-modal')).toBeInTheDocument();
  });

  test('does not open invitation modal when 2FA fails', async () => {
    mockInitiateTwoFaAuth.mockResolvedValue(false);

    renderWithWrappers(<InviteTeamMember />);

    const inviteButton = screen.getByText('Invite your developer as an admin');

    await act(async () => {
      fireEvent.click(inviteButton);
    });

    // Check that the modal is not displayed
    expect(screen.queryByTestId('invite-member-modal')).not.toBeInTheDocument();
  });

  test('opens video in new tab when video link is clicked', () => {
    // Mock window.open
    const originalOpen = window.open;
    window.open = jest.fn();

    renderWithWrappers(<InviteTeamMember />);

    const videoLink = screen.getByText('Watch a set up video');

    // Check that the link has the correct attributes
    expect(videoLink.closest('a')).toHaveAttribute('target', '_blank');
    expect(videoLink.closest('a')).toHaveAttribute('rel', 'noreferrer noopener');

    // Restore window.open
    window.open = originalOpen;
  });
});
