import React, { Fragment } from 'react';
import { Route, Routes } from 'react-router-dom';

import Breadcrumbs from '@apps/digital-bills/src/common/components/Breadcrumbs';
import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';

const mocks = { breadcrumbs: [{ label: 'Home' }, { label: 'PageA' }] };

describe('Breadcrumbs', () => {
  const App = (): React.ReactElement => {
    return (
      <Routes>
        <Route path="/home" element={<Fragment>Home</Fragment>} />
        <Route path="/home/PageA" element={<Breadcrumbs items={mocks.breadcrumbs} />} />
        <Route
          path="/home/PageA/PageB"
          element={<Breadcrumbs items={[...mocks.breadcrumbs, { label: 'PageB' }]} />}
        />
      </Routes>
    );
  };
  test('should render breadcrumbs', () => {
    const { getByText, getAllByRole } = renderWithWrappers(<App />, { route: '/home/PageA/PageB' });
    expect(getByText('Home')).toBeInTheDocument();
    expect(getByText('PageA')).toBeInTheDocument();
    expect(getByText('PageB')).toBeInTheDocument();

    // should render breadcrumbs with correct links
    const breadCrumbs = getAllByRole('link');
    expect(breadCrumbs.length).toEqual(3);
    expect(breadCrumbs[1]).toHaveAttribute('href', '/home/');
    expect(breadCrumbs[2]).toHaveAttribute('href', '/home/PageA/');

    // should go up one level route
    const backButton = getByText('Back');
    expect(backButton).toBeInTheDocument();
    backButton.click();
    expect(window.location.pathname).toEqual('/home/PageA/');
    expect(getByText('PageA')).toBeInTheDocument();

    // BreadcrumbNavLink
    const homeLink = getByText('Home');
    expect(getAllByRole('link')[1]).toHaveAttribute('href', '/home/');
    homeLink.click();
    expect(window.location.pathname).toEqual('/home/');
    expect(getByText('Home')).toBeInTheDocument();
  });
});
