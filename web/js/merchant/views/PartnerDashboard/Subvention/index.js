import { Routes, NavLink, Navigate, Route } from 'react-router-dom';
import { RouteGuard } from 'merchant/components/ShowWhen';
import { withRouter } from 'common/deprecated/withRouter';

import Transactional from './Transactional/List';
import Daily from './Daily/List';

function SubventionContainer() {
  return (
    <tabbed-container>
      <header>
        <NavLink end to="/partners/subventions/daily">
          Daily Subvention
        </NavLink>
        <NavLink end to="/partners/subventions/transactional">
          Subvention Per Transaction
        </NavLink>
      </header>
      <content>
        <Routes>
          <Route path="*" element={<Navigate to="/partners/subventions/daily" replace />} />

          <Route
            path="daily/*"
            element={
              <RouteGuard>
                <Daily />
              </RouteGuard>
            }
          />

          <Route
            path="transactional/*"
            element={
              <RouteGuard>
                <Transactional />
              </RouteGuard>
            }
          />
        </Routes>
      </content>
    </tabbed-container>
  );
}

export default withRouter(SubventionContainer);
