import React from 'react';
import { render } from 'test-utils';

import { ProviderShimmer } from '../ProviderShimmer';

describe('ProviderShimmer', () => {
  const renderApp = () => {
    return render(<ProviderShimmer />);
  };

  it('should render without any errors', () => {
    expect(() => renderApp()).not.toThrowError();
  });

  it('should render the fields', () => {
    const { container } = renderApp();
    expect(container).toMatchSnapshot();
  });
});
