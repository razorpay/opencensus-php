import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { Route, Routes, Navigate, MemoryRouter } from 'react-router-dom';
import { render, screen } from '@testing-library/react';

import BreadcrumbBack from 'merchant/views/BillMeSettings/common/components/Breadcrumbs/BreadcrumbBack';

const App = (): React.ReactElement => {
  return (
    <BladeProvider themeTokens={bladeTheme}>
      <MemoryRouter initialEntries={['/']}>
        <Routes>
          <Route path="/" element={<Navigate to="/pageA/pageB" replace />} />
          <Route path="/pageA" element={<>Page A</>} />
          <Route path="/pageA/pageB" element={<BreadcrumbBack />} />
        </Routes>
      </MemoryRouter>
    </BladeProvider>
  );
};

describe('BreadcrumbBack', () => {
  test('should render breadcrumbback', () => {
    render(<App />);
    expect(screen.getByText('Back')).toBeInTheDocument();
  });

  test('should go up one level route', () => {
    render(<App />);
    const backButton = screen.getByText('Back');
    backButton.click();
    expect(screen.getByText('Page A')).toBeInTheDocument();
  });
});
