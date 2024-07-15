import React from 'react';
import { render } from 'test-utils';

import { ClickOutside } from '../ClickOutside';

describe('ClickOutside', () => {
  it('should render', () => {
    const { container } = render(
      <ClickOutside onClickOutside={() => {}}>
        <div>ClickOutside</div>
      </ClickOutside>,
    );
    expect(container).toMatchSnapshot();
  });
});
