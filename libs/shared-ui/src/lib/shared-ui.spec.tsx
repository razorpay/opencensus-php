import React from 'react';
import { render } from '@testing-library/react';

import SharedUi from './shared-ui';

describe('SharedUi', () => {
  it('should render successfully', () => {
    const { baseElement } = render(<SharedUi app="remote" />);
    expect(baseElement).toBeTruthy();
  });
});
