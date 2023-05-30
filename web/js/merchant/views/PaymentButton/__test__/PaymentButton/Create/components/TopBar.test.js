import React from 'react';
import { render, screen, userEvent } from 'test-utils';
import TopBar from 'merchant/views/PaymentButton/PaymentButton/Create/components/TopBar';

describe('Topbar Component - UT', () => {
  test('payment TopBar component to be defined', () => {
    expect(TopBar).toBeDefined();
  });

  test('renders the title', () => {
    const title = 'Test Title';
    render(<TopBar title={title} />);
    const titleElement = screen.getByText(title);
    expect(titleElement).toBeInTheDocument();
  });

  test('does not render action buttons when `isActionsActive` is false', () => {
    const actionButtons = <button>Button</button>;
    render(<TopBar isActionsActive={false} actionButtons={actionButtons} />);
    const actionButtonsContainer = screen.queryByTestId('action-buttons');
    expect(actionButtonsContainer).not.toBeInTheDocument();
  });

  test('should render actionButtons when `isActionsActive` is true', () => {
    const actionButtons = <button>Button</button>;
    render(<TopBar isActionsActive={true} actionButtons={actionButtons} />);
    const btn = screen.getByText('Button');
    expect(btn).toBeInTheDocument();
  });

  test('should render x when handleClose Mock is defined', async () => {
    const actionButtons = <button>Button</button>;
    const handleCloseMock = jest.fn();
    render(
      <TopBar isActionsActive={true} actionButtons={actionButtons} handleClose={handleCloseMock} />,
    );
    const closeBtn = screen.getByText('×');
    expect(closeBtn).toBeInTheDocument();
    await userEvent.click(closeBtn);
    expect(handleCloseMock).toHaveBeenCalled();
  });
});
