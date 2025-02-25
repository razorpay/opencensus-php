import React from 'react';
import { Route, Routes, Navigate, NavLink } from 'react-router-dom';
import { TransitionGroup, CSSTransition } from 'react-transition-group';

import { withRouter } from '@libs/web-nexus/common/deprecated/withRouter';
import ErrorBoundary from '@libs/web-nexus/common/new-ui/ErrorBoundary';
import MainNavLink from 'razorx/components/MainNavLink';
import ModalContainer, { notifyError } from 'razorx/components/Modal';
import AsyncButton from 'razorx/components/ui/AsyncButton';
import adminFetch from 'razorx/helpers/admin-fetch';
import { org } from 'razorx/user';
import Experiments from 'razorx/views/Experiments';
import Features from 'razorx/views/Features';
import MerchantEvaluation from 'razorx/views/MerchantEvaluation';
import SplitzExclusionGroups from 'razorx/views/Splitz/ExclusionGroups';
import SplitzExperimentTester from 'razorx/views/Splitz/ExperimentTester';
import SplitzExperiments from 'razorx/views/Splitz/Experiments';
import SplitzProjects from 'razorx/views/Splitz/Projects';
import SplitzSegments from 'razorx/views/Splitz/Segments';
import WorkflowRequestsEntity from 'razorx/views/WorkflowRequests/Entity';
import WorkflowRequestsList from 'razorx/views/WorkflowRequests/List';

class RazorXApp extends React.Component {
  UNSAFE_componentWillMount() {
    loadCodeEditor();
  }

  handleLogout = () => {
    return adminFetch({
      url: '/admin/user/logout',
    }).then(() => {
      window.location.reload();
    });
  };

  render() {
    const paths = this.props.location.pathname.split('/');
    const isSplitz = location.pathname.includes('splitz');

    return (
      <div className="app-container RazorX-container">
        <Logo />
        <main>
          <ErrorBoundary resetOnProps location={this.props.location}>
            <TransitionGroup id="main-routes">
              <CSSTransition key={paths[1] || paths[0]} classNames="slide" timeout={420}>
                <div>
                  <Routes location={this.props.location}>
                    {/* Splitz Routes */}
                    <Route path="splitz/*">
                      <Route path="projects/:id?/*" element={<SplitzProjects />} />
                      <Route path="groups/:id?/*" element={<SplitzExclusionGroups />} />
                      <Route path="experiments/:id?/*" element={<SplitzExperiments />} />
                      <Route path="segments/:id?/*" element={<SplitzSegments />} />
                      <Route path="experiment-tester/*" element={<SplitzExperimentTester />} />
                    </Route>

                    {/* RazorX Routes */}
                    <Route path="/experiments/:id?" element={<Experiments />} />
                    <Route path="/features_flags/:id?" element={<Features />} />
                    <Route path="/requests" element={<WorkflowRequestsList />} />
                    <Route path="/requests/:id" element={<WorkflowRequestsEntity />} />
                    <Route path="/merchant-evaluation" element={<MerchantEvaluation />} />

                    <Route path="/splitz" element={<Navigate to="/splitz/experiments" replace />} />
                    <Route path="*" element={<Navigate to="/experiments" replace />} />
                  </Routes>
                </div>
              </CSSTransition>
            </TransitionGroup>
          </ErrorBoundary>
        </main>
        {/* eslint-disable-next-line no-undef */}
        <Sidebar user={user} handleLogout={this.handleLogout} isSplitz={isSplitz} />
        <ModalContainer />
      </div>
    );
  }
}

const links = [
  // title, url, permission, icon
  ['Experiments', '/experiments', '', 'i-flask'],
  ['Features', '/features_flags', '', 'i-layers'],
  ['Workflow Requests', '/requests', '', 'i-yes'],
  ['Merchant Evaluation', '/merchant-evaluation', '', 'i-user-search'],
];

const splitzLinks = [
  // title, url, permission, icon
  ['Experiments', '/splitz/experiments', '', 'fa fa-flask'],
  ['Projects', '/splitz/projects', '', 'i-layers'],
  ['Exclusion Groups', '/splitz/groups', '', 'fa fa-columns'],
  ['Segments', '/splitz/segments', '', 'fa fa-object-group'],
  ['Experiment Tester', '/splitz/experiment-tester', '', 'fa fa-search'],
];

export const Sidebar = ({ user, handleLogout, isSplitz }) => (
  <aside className={`org-${org.custom_code}`}>
    <a id="razorx-logo" href="/admin/razorx">
      <img src="/img/logo.png" height="28px" />
    </a>
    <div className="scroll-nav" style={{ display: 'flex', flexDirection: 'column' }}>
      {isSplitz
        ? splitzLinks.map((l, i) => (
            <div key={i}>
              <MainNavLink to={l[1]} permission={l[2]} icon={l[3]}>
                {l[0]}
              </MainNavLink>
            </div>
          ))
        : links.map((l, i) => (
            <div key={i}>
              <MainNavLink to={l[1]} permission={l[2]} icon={l[3]}>
                {l[0]}
              </MainNavLink>
            </div>
          ))}
      <div style={{ marginTop: 'auto' }}>
        {isSplitz ? (
          <React.Fragment>
            <a
              className="main-nav"
              style={{ cursor: 'help' }}
              href="https://docs.google.com/document/d/15KBvtGsy4qlPe9wuOUwLja9uFXXALPfK0pLIjQ1JmxA"
              target="_blank"
              rel="noopener noreferrer"
            >
              <i className="fa fa-question-circle" />
              How to use
            </a>
            <NavLink className="main-nav" to="/razorx">
              {/* <i className="i-flask" /> */}
              <i className="fa fa-xing" />
              Go to RazorX
            </NavLink>
          </React.Fragment>
        ) : (
          <NavLink className="main-nav" to="/splitz">
            <i className="fa fa-xing" />
            Go to Splitz
          </NavLink>
        )}
      </div>
    </div>
    <div id="profile-nav" className="main-nav">
      <i className="fa fa-user-circle" />
      <div className="ellipsis-wrap">{user.name}</div>
      <div className="menu">
        <a
          href="https://dashboard.razorpay.com/admin/profile"
          className="btn-default"
          target="_blank"
          rel="noopener noreferrer"
        >
          Profile
        </a>
        <AsyncButton
          onClick={handleLogout}
          className="logout-btn btn-default"
          pendingClass="logout-btn btn-default btn-pending"
        >
          Logout
          <span className="spin-btn" />
        </AsyncButton>
      </div>
    </div>
  </aside>
);

export function Logo() {
  return (
    <div className="page-center" id="page-logo">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        style={{ width: 80, fill: 'rgba(255,255,255,0.06)' }}
      >
        <path d="M 8 3 L 8 5 L 9 5 L 9 10 L 3.4316406 17.773438 C 3.1656406 18.112437 3 18.535 3 19 C 3 20.105 3.895 21 5 21 L 19 21 C 20.105 21 21 20.105 21 19 C 21 18.535 20.834359 18.112437 20.568359 17.773438 L 15 10 L 15 5 L 16 5 L 16 3 L 8 3 z M 11 5 L 13 5 L 13 9 L 11 9 L 11 5 z M 10.744141 11 L 13.255859 11 L 18.943359 18.9375 L 18.972656 18.966797 L 19 19 L 5.0058594 19.007812 L 5.03125 18.972656 L 5.0566406 18.9375 L 10.744141 11 z M 13 13 A 1 1 0 0 0 12 14 A 1 1 0 0 0 13 15 A 1 1 0 0 0 14 14 A 1 1 0 0 0 13 13 z M 10.5 15 A 1.5 1.5 0 0 0 9 16.5 A 1.5 1.5 0 0 0 10.5 18 A 1.5 1.5 0 0 0 12 16.5 A 1.5 1.5 0 0 0 10.5 15 z" />
      </svg>
      <div>
        Split<strong>Z</strong>
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

export default withRouter(RazorXApp);
