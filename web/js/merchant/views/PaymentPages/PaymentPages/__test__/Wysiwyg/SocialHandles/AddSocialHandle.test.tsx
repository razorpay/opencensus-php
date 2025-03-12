import React from 'react';
import { render, screen, fireEvent, waitFor, userEvent } from 'test-utils';
import AddSocialMediaDetails from 'merchant/views/PaymentPages/PaymentPages/CreateEdit/Storefront/SocialHandleDetails/AddSocialMediaDetails';
import SocialHandleDrawerContainer from 'merchant/views/PaymentPages/PaymentPages/CreateEdit/Storefront/SocialHandleDetails/SocialHandleDrawerContainer';
import SocialHandleDrawerView from '../../../CreateEdit/Storefront/SocialHandleDetails/SocialHandleDrawerView';
import {
  defaultAddSocialMediaDetailsProps,
  mockSocialHandleModalProps,
  mockSocialHandleListProps,
} from './mock';
import { SocialHandleModal } from '../../../CreateEdit/Storefront/SocialHandleDetails/SocialHandleContent';
import SocialHandleList from '../../../CreateEdit/Storefront/SocialHandleDetails/SocialHandleList';

describe('AddSocialMediaDetails Component', () => {
  const mockHandleClick = jest.fn();
  const mockSetShowSocialMediaAlert = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders LineItems when drawer is closed', () => {
    render(
      <AddSocialMediaDetails
        {...defaultAddSocialMediaDetailsProps}
        handleClick={mockHandleClick}
        setShowSocialMediaAlert={mockSetShowSocialMediaAlert}
      />,
    );

    expect(screen.getByText('Add social handles')).toBeInTheDocument();
    expect(screen.getByText('Turn on social handles preview')).toBeInTheDocument();
  });

  it('handles alert primary button click', () => {
    render(
      <AddSocialMediaDetails
        {...defaultAddSocialMediaDetailsProps}
        handleAddSocialMediaClick={mockHandleClick}
        setShowSocialMediaAlert={mockSetShowSocialMediaAlert}
        showSocialMedialAlert={true}
      />,
    );
    expect(screen.getByText(/Missing social handles/i)).toBeInTheDocument();

    const primaryButton = screen.getByText('Upload Social Handles');
    fireEvent.click(primaryButton);

    expect(mockSetShowSocialMediaAlert).toHaveBeenCalled();
    const updateFunction = mockSetShowSocialMediaAlert.mock.calls[0][0];
    const previousState = { showSocialHandleAlert: true, showBannerAlert: true };
    const result = updateFunction(previousState);
    expect(result).toEqual({
      showSocialHandleAlert: false,
      showBannerAlert: true,
    });
    expect(mockHandleClick).toHaveBeenCalledWith(true);
  });

  it('handles alert secondary button click', () => {
    render(
      <AddSocialMediaDetails
        {...defaultAddSocialMediaDetailsProps}
        handleAddSocialMediaClick={mockHandleClick}
        setShowSocialMediaAlert={mockSetShowSocialMediaAlert}
        showSocialMedialAlert={true}
      />,
    );

    expect(screen.getByText(/Missing social handles/i)).toBeInTheDocument();

    const disableButton = screen.getByText('Disable Social Handles');
    fireEvent.click(disableButton);

    expect(mockSetShowSocialMediaAlert).toHaveBeenCalled();
    const updateFunction = mockSetShowSocialMediaAlert.mock.calls[0][0];

    const previousState = { showSocialHandleAlert: true, showBannerAlert: true };
    const result = updateFunction(previousState);
    expect(result).toEqual({
      showSocialHandleAlert: false,
      showBannerAlert: true,
    });
  });
});

describe('SocialHandleDrawerContainer Component', () => {
  const selectedHandle = {
    name: 'instagram',
    label: 'Instagram',
    src: 'https://s3.ap-south-1.amazonaws.com/rzp-prod-merchant-assets/payment-link/description/q2enzzenkbxdxm.jpeg',
    inputLabel: 'Add your instagram profile link',
    inputPlaceholder: 'https://www.instagram.com/rsubhojit/',
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders social handle drawer', () => {
    render(<SocialHandleDrawerContainer />);

    expect(screen.getByText('Select a social account')).toBeInTheDocument();
    expect(screen.getByText('Build trust with your customers')).toBeInTheDocument();
    expect(screen.getByText('You can add a maximum of 4 social accounts')).toBeInTheDocument();
  });

  it('handles adding new social handle', async () => {
    render(<SocialHandleDrawerView />);

    const addButton = screen.getByLabelText('add social handle modal opener');
    fireEvent.click(addButton);

    render(<SocialHandleModal {...mockSocialHandleModalProps} selectedHandle={null} />);

    await waitFor(() => {
      expect(screen.getByTestId('social-handle-item-instagram')).toBeInTheDocument();
    });

    const instagramButton = screen.getByTestId('social-handle-item-instagram');
    fireEvent.click(instagramButton);

    render(<SocialHandleModal {...mockSocialHandleModalProps} selectedHandle={selectedHandle} />);

    await waitFor(() => {
      expect(screen.getByText('Add your instagram profile link')).toBeInTheDocument();
    });
    await userEvent.type(
      screen.getByPlaceholderText('https://www.instagram.com/rsubhojit/'),
      'https://www.instagram.com/username',
    );

    const saveButton = screen.getByLabelText('save social handle');
    fireEvent.click(saveButton);

    render(<SocialHandleList {...mockSocialHandleListProps} />);

    expect(screen.getByText('Your added banner images')).toBeInTheDocument();
    expect(screen.getByTestId('single-social-handle-instagram')).toBeInTheDocument();
  });
});
