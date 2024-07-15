import React from 'react';
import { render } from 'test-utils';

import { FullPageCover } from '../FullPageCover';

describe('FullPageCover', () => {
  it('should render', () => {
    const { container } = render(
      <FullPageCover>
        <div>FullPageCover</div>
      </FullPageCover>,
    );
    expect(container).toMatchSnapshot();
  });
});
