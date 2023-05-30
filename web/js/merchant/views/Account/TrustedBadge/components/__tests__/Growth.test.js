import React from 'react';
import { render } from 'test-utils';
import Growth from 'merchant/views/Account/TrustedBadge/components/Growth';

describe('Growth', () => {
  const props = {
    title: 'Test Title',
    features: [
      {
        title: 'Feature 1',
        icon: 'icon1.png',
        desc: 'Description 1',
      },
      {
        title: 'Feature 2',
        icon: 'icon2.png',
        desc: 'Description 2',
      },
    ],
  };

  const renderApp = () => {
    return render(<Growth {...props} />);
  };

  it('renders the correct title', () => {
    const { getByText } = renderApp();
    expect(getByText(props.title)).toBeInTheDocument();
  });

  it('renders the correct number of features', () => {
    const { getAllByTestId } = renderApp();
    expect(getAllByTestId('rtb-feature-item')).toHaveLength(props.features.length);
  });

  it('renders the correct feature title and description', () => {
    const { getAllByTestId } = renderApp();
    props.features.forEach((feature, index) => {
      expect(getAllByTestId('rtb-feature-title')[index]).toHaveTextContent(feature.title);
      expect(getAllByTestId('rtb-feature-desc')[index]).toHaveTextContent(feature.desc);
    });
  });
});
