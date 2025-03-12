import React from 'react';
import { render, screen, fireEvent } from 'test-utils';
import SocialHandleList from 'merchant/views/PaymentPages/PaymentPages/CreateEdit/Storefront/SocialHandleDetails/SocialHandleList';

const mockSocialHandles = [
  {
    platform: 'facebook',
    profile_url: 'https://facebook.com/test',
    position: 0,
    logo_url: 'facebook-logo.png',
  },
  {
    platform: 'instagram',
    profile_url: 'https://instagram.com/test',
    position: 1,
    logo_url: 'instagram-logo.png',
  },
  {
    platform: 'twitter',
    profile_url: 'https://twitter.com/test',
    position: 2,
    logo_url: 'twitter-logo.png',
  },
];

describe('SocialHandleList Component', () => {
  const mockReorderSocialHandle = jest.fn();
  const mockHandleEditClick = jest.fn();
  const mockHandleDeleteClick = jest.fn();

  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders social handle list with all items', () => {
    render(
      <SocialHandleList
        socialHandleList={mockSocialHandles}
        isMobile={false}
        reorderSocialHandle={mockReorderSocialHandle}
        editSocialHandle={mockHandleEditClick}
        showDeleteConfirmation={mockHandleDeleteClick}
      />,
    );
    mockSocialHandles.forEach((handle) => {
      expect(screen.getByTestId(`single-social-handle-${handle.platform}`)).toBeInTheDocument();
    });
    expect(screen.getByText('Your added banner images')).toBeInTheDocument();
    mockSocialHandles.map(({ platform }) => {
      expect(screen.getByTestId(`single-social-handle-${platform}`)).toBeInTheDocument();
    });
  });

  it('handles reordering of social handles', () => {
    render(
      <SocialHandleList
        socialHandleList={mockSocialHandles}
        isMobile={false}
        reorderSocialHandle={mockReorderSocialHandle}
        editSocialHandle={mockHandleEditClick}
        showDeleteConfirmation={mockHandleDeleteClick}
      />,
    );

    const oldIndex = 0;
    const newIndex = 2;

    const sortableContainer = screen.getByText('Your added banner images').parentElement as Element;
    fireEvent(
      sortableContainer,
      new CustomEvent('sortend', {
        detail: { oldIndex, newIndex },
      }),
    );
  });

  it('renders single social handle with correct logo and buttons', () => {
    render(
      <SocialHandleList
        socialHandleList={mockSocialHandles}
        isMobile={false}
        reorderSocialHandle={mockReorderSocialHandle}
        editSocialHandle={mockHandleEditClick}
        showDeleteConfirmation={mockHandleDeleteClick}
      />,
    );
    mockSocialHandles.map((handle) => {
      expect(screen.getByAltText(handle.platform)).toBeInTheDocument();
      expect(screen.getByLabelText(`edit icon ${handle.platform}`)).toBeInTheDocument();
      expect(screen.getByLabelText(`delete icon ${handle.platform}`)).toBeInTheDocument();
    });
  });

  it('calls handleEdit when edit button is clicked', async () => {
    render(
      <SocialHandleList
        socialHandleList={mockSocialHandles}
        isMobile={false}
        reorderSocialHandle={mockReorderSocialHandle}
        editSocialHandle={mockHandleEditClick}
        showDeleteConfirmation={mockHandleDeleteClick}
      />,
    );

    const editButton = screen.getByLabelText('edit icon facebook');
    fireEvent.click(editButton);

    expect(mockHandleEditClick).toHaveBeenCalledWith('facebook');
  });

  it('calls handleDelete when delete button is clicked', () => {
    render(
      <SocialHandleList
        socialHandleList={mockSocialHandles}
        isMobile={false}
        reorderSocialHandle={mockReorderSocialHandle}
        editSocialHandle={mockHandleEditClick}
        showDeleteConfirmation={mockHandleDeleteClick}
      />,
    );

    const deleteButton = screen.getByLabelText('delete icon facebook');
    fireEvent.click(deleteButton);

    expect(mockHandleDeleteClick).toHaveBeenCalledWith('facebook');
  });
});
