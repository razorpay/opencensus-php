import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { Route, Routes, Navigate, MemoryRouter } from 'react-router-dom';
import { render, screen } from '@testing-library/react';

import BreadcrumbNavLink from 'merchant/views/BillMeSettings/common/components/Breadcrumbs/BreadcrumbNavLink';

const App = (): React.ReactElement => {
  return (
    <BladeProvider themeTokens={bladeTheme}>
      <MemoryRouter initialEntries={['/']}>
        <Routes>
          <Route path="/" element={<Navigate to="/pageA/pageB" replace />} />
          <Route path="/pageA" element={<>This is Page A content</>} />
          <Route
            path="/pageA/pageB"
            element={<BreadcrumbNavLink to="../">Page A</BreadcrumbNavLink>}
          />
        </Routes>
      </MemoryRouter>
    </BladeProvider>
  );
};

describe('BreadcrumbNavLink', () => {
  test('should render BreadcrumbNavLink', () => {
    render(<App />);
    expect(screen.getByText('Page A')).toBeInTheDocument();
    expect(screen.getByRole('link')).toHaveAttribute('href', '/pageA/');
  });

  test('should redirect to Home on link click', () => {
    render(<App />);
    const backButton = screen.getByText('Page A');
    backButton.click();
    expect(screen.getByText('This is Page A content')).toBeInTheDocument();
  });
});
