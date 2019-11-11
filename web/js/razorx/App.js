import ErrorBoundary from 'common/ErrorBoundary';

import { Route, Switch, Redirect, Link, withRouter } from 'react-router-dom';
import { TransitionGroup, CSSTransition } from 'react-transition-group';
import { ShowWhenRoute } from 'razorx/components/ShowWhen';
import AsyncButton from 'razorx/components/ui/AsyncButton';

import { notifyError } from 'razorx/components/Modal';
import MainNavLink from 'razorx/components/MainNavLink';

import user, { org } from 'razorx/user';

import Experiments from 'razorx/views/Experiments';
import Features from 'razorx/views/Features';
import WorkflowRequestsList from 'razorx/views/WorkflowRequests/List';
import WorkflowRequestsEntity from 'razorx/views/WorkflowRequests/Entity';
import MerchantEvaluation from 'razorx/views/MerchantEvaluation';

import ModalContainer, {
  openSlider,
  closeSlider,
} from 'razorx/components/Modal';

import adminFetch from 'razorx/helpers/admin-fetch';

@withRouter
export default class RazorXApp extends React.Component {
  componentWillMount() {
    loadCodeEditor();
  }

  handleLogout = () => {
    return adminFetch({
      url: '/admin/user/logout',
    }).then(r => {
      window.location.reload();
    });
  };

  render() {
    const paths = this.props.location.pathname.split('/');

    return (
      <div class="app-container RazorX-container">
        <Logo />
        <main>
          <ErrorBoundary resetOnProps location={this.props.location}>
            <TransitionGroup id="main-routes">
              <CSSTransition
                key={paths[1] || paths[0]}
                classNames="slide"
                timeout={420}
              >
                <div>
                  <Switch location={this.props.location}>
                    <Route path="/experiments" component={Experiments} exact />
                    <Route
                      path="/experiments/:id"
                      component={Experiments}
                      exact
                    />

                    <ShowWhenRoute
                      path="/features_flags"
                      component={Features}
                      exact
                    />
                    <Route
                      path="/features_flags/:id"
                      component={Features}
                      exact
                    />

                    <Route
                      path="/requests"
                      component={WorkflowRequestsList}
                      exact
                    />
                    <Route
                      path="/requests/:id(w_action_.+)"
                      component={WorkflowRequestsEntity}
                      exact
                    />

                    <Route
                      path="/merchant-evaluation"
                      component={MerchantEvaluation}
                    />
                    <Redirect to="/experiments" />
                  </Switch>
                </div>
              </CSSTransition>
            </TransitionGroup>
          </ErrorBoundary>
        </main>
        <Sidebar user={user} handleLogout={this.handleLogout} />
        <ModalContainer />
      </div>
    );
  }
}

const links = [
  // title, url, permission, icon
  ['Experiments', '/experiments', '', 'flask'],
  ['Features', '/features_flags', '', 'layers'],
  ['Workflow Requests', '/requests', '', 'yes'],
  ['Merchant Evaluation', '/merchant-evaluation', '', 'user-search'],
];

export const Sidebar = ({ user, handleLogout }) => (
  <aside className={`org-${org.custom_code}`}>
    <a id="razorx-logo" href="/admin/razorx">
      <img src="/img/logo.png" height="28px" />
    </a>
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
        <a
          href="https://dashboard.razorpay.com/admin/profile"
          class="btn-default"
          target="_blank"
        >
          Profile
        </a>
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

export function Logo() {
  return (
    <div class="page-center" id="page-logo">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        style={{ width: 80, fill: 'rgba(255,255,255,0.06)' }}
      >
        <path d="M 8 3 L 8 5 L 9 5 L 9 10 L 3.4316406 17.773438 C 3.1656406 18.112437 3 18.535 3 19 C 3 20.105 3.895 21 5 21 L 19 21 C 20.105 21 21 20.105 21 19 C 21 18.535 20.834359 18.112437 20.568359 17.773438 L 15 10 L 15 5 L 16 5 L 16 3 L 8 3 z M 11 5 L 13 5 L 13 9 L 11 9 L 11 5 z M 10.744141 11 L 13.255859 11 L 18.943359 18.9375 L 18.972656 18.966797 L 19 19 L 5.0058594 19.007812 L 5.03125 18.972656 L 5.0566406 18.9375 L 10.744141 11 z M 13 13 A 1 1 0 0 0 12 14 A 1 1 0 0 0 13 15 A 1 1 0 0 0 14 14 A 1 1 0 0 0 13 13 z M 10.5 15 A 1.5 1.5 0 0 0 9 16.5 A 1.5 1.5 0 0 0 10.5 18 A 1.5 1.5 0 0 0 12 16.5 A 1.5 1.5 0 0 0 10.5 15 z" />
      </svg>
      <div>
        Razor<strong>X</strong>
      </div>
    </div>
  );
}

function loadCodeEditor() {
  const script = document.createElement('script');

  script.onerror = () => {
    notifyError('JSON Editor failed to load. Reload the page to Retry.');
  };

  script.src = 'https://unpkg.com/codeflask/build/codeflask.min.js';

  document.head.appendChild(script);
}
