import React from 'react';
import { screen, render as rtlRender } from '@testing-library/react';
import { WorkspaceWrapper } from '../WorkspaceWrapper';
import { render } from '@apps/shell/src/services/test/render';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';

describe('WorkspaceWrapper', () => {
  test('renders children correctly', () => {
    render(
      <WorkspaceWrapper>
        <div data-testid="test-child">Test Child</div>
      </WorkspaceWrapper>,
    );

    expect(screen.getByTestId('test-child')).toBeInTheDocument();
  });
});
