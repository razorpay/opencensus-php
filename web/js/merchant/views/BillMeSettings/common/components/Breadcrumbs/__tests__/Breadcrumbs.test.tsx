import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { Route, Routes, Navigate, MemoryRouter, Location } from 'react-router-dom';
import { render, screen } from '@testing-library/react';

import Breadcrumbs from 'merchant/views/BillMeSettings/common/components/Breadcrumbs/Breadcrumbs';

const mocks = {
  breadcrumbs: [{ label: 'PageA' }, { label: 'PageB' }],
};

const App = (): React.ReactElement => {
  return (
    <BladeProvider themeTokens={bladeTheme}>
      <MemoryRouter initialEntries={['/']}>
        <Routes>
          <Route path="/" element={<Navigate to="/pageA/pageB" replace />} />
          <Route path="/pageA" element={<>This is Page A content</>} />
          <Route path="/pageA/pageB" element={<Breadcrumbs items={mocks.breadcrumbs} />} />
        </Routes>
      </MemoryRouter>
    </BladeProvider>
  );
};

describe('Breadcrumbs', () => {
  let location: Location;

  beforeEach(() => {
    location = window.location;
  });

  afterEach(() => {
    window.location = location;
  });

  test('should render Breadcrumbs', () => {
    render(<App />);
    expect(screen.getByText('PageA')).toBeInTheDocument();
    expect(screen.getByText('PageB')).toBeInTheDocument();
    expect(screen.getByText('Back')).toBeInTheDocument();
  });

  test('should render Breadcrumbs with current page disabled', () => {
    Object.defineProperty(window, 'location', {
      value: { pathname: '/pageA/pageB' },
      writable: true,
      configurable: true,
    });

    render(<App />);

    const breadCrumbs = document.querySelectorAll('li');
    const disabledLink = breadCrumbs[breadCrumbs.length - 1].querySelector("a[role='link']");
    expect(disabledLink).toBeNull();
    const lastBreadCrumb = screen.getByText('PageB');
    expect(lastBreadCrumb).toBeInTheDocument();
    expect(breadCrumbs).toHaveLength(3);
  });

  test('should render breadcrumbs with correct links', () => {
    render(<App />);
    const breadCrumbs = screen.getAllByRole('link');
    expect(breadCrumbs.length).toEqual(4);
    expect(breadCrumbs[2]).toHaveAttribute('href', '/pageA/');
    expect(breadCrumbs[3]).toHaveAttribute('href', '/pageA/pageB');
  });

  test('should go up one level route', () => {
    render(<App />);
    const backButton = screen.getByText('Back');
    backButton.click();
    expect(screen.getByText('This is Page A content')).toBeInTheDocument();
  });
});
