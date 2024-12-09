import React from 'react';
import { render, screen } from 'test-utils';
import LineItems from 'merchant/views/PaymentPages/PaymentPages/CreateEdit/Storefront/LineItems';

describe('Storefron LineItems Component', () => {
  it('renders title and subtitle correctly', () => {
    render(
      <LineItems
        title="Test Title"
        subTitle="This is a test subtitle"
        rightChildren={null}
        extraItems={null}
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
      />,
    );

    expect(screen.getByText('Extra Item Component')).toBeInTheDocument();
  });

  it('renders correctly without rightChildren and extraItems', () => {
    render(
      <LineItems
        title="Test Title"
        subTitle="This is a test subtitle"
        rightChildren={null}
        extraItems={null}
      />,
    );

    expect(screen.getByText('Test Title')).toBeInTheDocument();
    expect(screen.getByText('This is a test subtitle')).toBeInTheDocument();
    expect(screen.queryByText('Right Child Component')).not.toBeInTheDocument();
    expect(screen.queryByText('Extra Item Component')).not.toBeInTheDocument();
  });
});
