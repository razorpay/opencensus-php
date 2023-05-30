import React from 'react';
import { render } from 'test-utils';
import Details from 'merchant/views/Account/TrustedBadge/components/Details';

describe('Details component', () => {
  const props = {
    headClass: 'test-head-class',
    title: 'Test Title',
    subtitle: 'Test Subtitle',
    details: ['Test Detail 1', 'Test Detail 2'],
    imgSrc: 'test-image-src',
    subComponent: ['Test Subcomponent'],
    className: 'test-class',
    handleSubComponent: jest.fn(),
  };

  const renderApp = () => {
    return render(<Details {...props} />);
  };

  it('renders the correct title', () => {
    const { getByText } = renderApp();
    expect(getByText('Test Title')).toBeInTheDocument();
  });

  it('renders the correct subtitle', () => {
    const { getByText } = renderApp();
    expect(getByText('Test Subtitle')).toBeInTheDocument();
  });

  it('renders the correct number of details', () => {
    const { getAllByTestId } = renderApp();
    expect(getAllByTestId('list-item')).toHaveLength(2);
  });

  it('renders the correct image source', () => {
    const { getByRole } = renderApp();
    expect(getByRole('image')).toHaveAttribute('src', 'test-image-src');
  });

  it('renders the correct subcomponent', () => {
    renderApp();
    expect(props.handleSubComponent).toHaveBeenCalledWith('Test Subcomponent');
  });
});
