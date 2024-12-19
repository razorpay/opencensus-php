// web/js/merchant/views/onboarding/FullPageViewWrapper.test.tsx
import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { render, screen } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';

import FullPageViewWrapper from 'merchant/views/onboarding/FullPageViewWrapper';

const customRender = (children: React.ReactNode) => {
  return render(
    <BrowserRouter>
      <BladeProvider themeTokens={bladeTheme}>
        <FullPageViewWrapper>{children}</FullPageViewWrapper>
      </BladeProvider>
    </BrowserRouter>,
  );
};

describe('FullPageViewWrapper', () => {
  it('renders the Go Back link', () => {
    customRender(<div>Test Content</div>);
    const backLink = screen.getByText(/Back/i);
    expect(backLink).toBeInTheDocument();
  });

  it('renders the children passed', () => {
    customRender(<div>Test Content</div>);
    const childContent = screen.getByText(/Test Content/i);
    expect(childContent).toBeInTheDocument();
  });
});
