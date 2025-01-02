import React from 'react';
import { render, screen } from 'test-utils';
import LineItems from 'merchant/views/Settings/Configuration/components/Configuration/LineItems';

// Mock for the notifications and context functions
jest.mock('merchant/views/Settings/Configuration/CheckoutEditor/context', () => ({
  useCheckoutEditor: jest.fn(),
}));
jest.mock('merchant_common/reducers/notifications', () => ({
  showNotification: jest.fn(),
}));
jest.mock('merchant/views/Settings/Configuration/CheckoutEditor/context', () => ({
  useCheckoutEditor: jest.fn().mockReturnValue({
    handleFeedbackSubmit: jest.fn(),
  }),
}));
jest.mock('merchant/views/Settings/Configuration/CheckoutEditor/track', () => ({
  sendToSegment: jest.fn(),
}));

describe('LineItems', () => {
  it('renders title and subtitle correctly', () => {
    render(
      <LineItems
        title="Test Title"
        subTitle="This is a test subtitle"
        rightChildren={null}
        extraItems={null}
        blockData={null}
        showFeedback={false}
      />,
    );

    expect(screen.getByText('Test Title')).toBeInTheDocument();
    expect(screen.getByText('This is a test subtitle')).toBeInTheDocument();
  });

  it('renders rightChildren if provided', () => {
    const mockRightChild = <div>Right Child Component</div>;

    render(
      <LineItems
        title="Test Title"
        subTitle="This is a test subtitle"
        rightChildren={mockRightChild}
        extraItems={null}
        blockData={null}
        showFeedback={false}
      />,
    );

    expect(screen.getByText('Right Child Component')).toBeInTheDocument();
  });

  it('renders extraItems if provided', () => {
    const mockExtraItem = <div>Extra Item Component</div>;

    render(
      <LineItems
        title="Test Title"
        subTitle="This is a test subtitle"
        rightChildren={null}
        extraItems={mockExtraItem}
        blockData={null}
        showFeedback={false}
      />,
    );

    expect(screen.getByText('Extra Item Component')).toBeInTheDocument();
  });

  it('should render new tag if blockData contains a "new" tag', () => {
    const mockBlockData = { tags: [{ tag: 'new' }] };

    render(
      <LineItems
        title="Test Title"
        subTitle="This is a test subtitle"
        rightChildren={null}
        extraItems={null}
        blockData={mockBlockData}
        showFeedback={false}
      />,
    );

    expect(screen.getByText('New')).toBeInTheDocument();
  });

  it('should not render new tag if blockData does not contain a "new" tag', () => {
    const mockBlockData = { tags: [] };

    render(
      <LineItems
        title="Test Title"
        subTitle="This is a test subtitle"
        rightChildren={null}
        extraItems={null}
        blockData={mockBlockData}
        showFeedback={false}
      />,
    );

    expect(screen.queryByText('New')).not.toBeInTheDocument();
  });

  it('should render feedback section when showFeedback is true', () => {
    render(
      <LineItems
        title="Test Title"
        subTitle="This is a test subtitle"
        rightChildren={null}
        extraItems={null}
        blockData={{ id: '1', is_feedback_taken: false }}
        showFeedback={true}
      />,
    );

    expect(screen.getByText('Did you like this feature?')).toBeInTheDocument();
  });

  it('should not show feedback section after submission', () => {
    const mockBlockData = { id: '1', is_feedback_taken: true };

    render(
      <LineItems
        title="Test Title"
        subTitle="This is a test subtitle"
        rightChildren={null}
        extraItems={null}
        blockData={mockBlockData}
        showFeedback={true}
      />,
    );

    expect(screen.queryByText('Did you like this feature?')).not.toBeInTheDocument();
  });
});
