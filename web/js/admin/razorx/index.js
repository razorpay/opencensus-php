import ErrorBoundary from 'common/ErrorBoundary';

import { Route, Switch, Redirect, Link, withRouter } from 'react-router-dom';

import { ShowWhenRoute } from 'admin/components/ShowWhen';
import AsyncButton from 'ui/AsyncButton';

import MainNavLink from 'admin/components/MainNavLink';

import Features from './Features';
import Experiments from './Experiments';
import user, { org } from 'admin/user';
import WorkflowsList from './workflows/List';
import MerchantEvaluation from './merchant_evaluation/Search';
import AuditLogsList from './auditlog/List';

import ModalContainer, { openSlider, closeSlider } from 'common/modal';

@withRouter
export default class RazorXApp extends React.Component {
  handleLogout = () => {
    return fetch({
      url: '/admin/user/logout',
    }).then(r => {
      window.location.reload();
    });
  };

  render() {
    return (
      <div id="app-container">
        <main>
          <ErrorBoundary resetOnProps location={this.props.location}>
            <Switch>
              <Route path="/razorx/experiments" component={Experiments} />
              <ShowWhenRoute path="/razorx/features" component={Features} />
              <Route path="/razorx/audit-log" component={AuditLogList} />
              <Redirect to="/razorx/experiments" />
            </Switch>
          </ErrorBoundary>
        </main>
        <header>
          <div id="profile-icon">
            {user.name}
            <i class="i-arrow-down" />
            <div class="menu">
              <Link to="/profile">
                <i class="i-user" />
                Profile
              </Link>
              <AsyncButton
                onClick={this.handleLogout}
                class="logout-btn btn-default"
                pendingClass="logout-btn btn-default btn-pending"
              >
                <i class="i-logout" />
                Logout
                <span class="spin-btn" />
              </AsyncButton>
            </div>
          </div>
        </header>
        <Sidebar />
        <ModalContainer />
      </div>
    );
  }
}

const links = [
  // title, url, permission, icon
  ['Experiments', '/razorx/experiments', '', 'flask'],
  ['Features', '/razorx/features', '', 'layers'],
  ['Workflows', '/razorx/workflows', '', 'yes'],
  ['Merchant Evaluation', '/razorx/merchant-evaluation', '', 'user-search'],
  ['Audit Logs', '/razorx/audit-logs', '', 'notes'],
];

export const Sidebar = ({ user, handleLogout }) => (
  <aside className={`org-${org.custom_code}`}>
    <a id="razorx-logo" href="/admin/razorx" />
    <div class="scroll-nav">
      {links.map((l, i) => (
        <div key={i}>
          <MainNavLink to={l[1]} permission={l[2]} icon={l[3]}>
            {l[0]}
          </MainNavLink>
        </div>
      ))}
    </div>
    <div id="profile-nav" class="main-nav">
      <i class="i-user-circle" />
      <div class="ellipsis-wrap">{user.name}</div>
      <div class="menu">
        <Link to="/profile" class="btn-default">
          Profile
        </Link>
        <AsyncButton
          onClick={handleLogout}
          class="logout-btn btn-default"
          pendingClass="logout-btn btn-default btn-pending"
        >
          Logout
          <span class="spin-btn" />
        </AsyncButton>
      </div>
    </div>
  </aside>
);

export default class RazorX extends React.Component {
  componentWillMount() {
    document.title = 'RazorX Dashboard';
  }

  componentWillUnmount() {
    document.title = 'Razorpay - Admin Panel';
  }

  render() {
    return (
      <Switch>
        <Route path="/razorx/experiments" component={Experiments} />
        <ShowWhenRoute path="/razorx/features" component={Features} />
        <Route path="/razorx/workflows" component={WorkflowsList} />
        <Route
          path="/razorx/merchant-evaluation"
          component={MerchantEvaluation}
        />
        <Route path="/razorx/audit-logs" component={AuditLogsList} />
        <Redirect to="/razorx/experiments" />
      </Switch>
    );
  }
}

RazorX.display_name = 'RazorX';
