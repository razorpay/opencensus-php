import React from 'react';
import { render } from 'test-utils';

import { FullPageCoverHeader } from '../FullPageCoverHeader';

describe('FullPageCoverHeader', () => {
  it('should render', () => {
    const { container } = render(
      <FullPageCoverHeader>
        <div>FullPageCoverHeader</div>
      </FullPageCoverHeader>,
    );
    expect(container).toMatchSnapshot();
  });
});
