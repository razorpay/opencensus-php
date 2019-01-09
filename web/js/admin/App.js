import 'common/polyfill';
import React, { Component } from 'react';
import {
  Route,
  matchPath,
  Switch,
  Redirect,
  Link,
  withRouter,
} from 'react-router-dom';
import { matchFullPageView } from './routes/helper';

import MainContent, { Sidebar as MainSidebar } from './routes';

import ModalContainer, { openSlider, closeSlider } from 'common/modal';
import ErrorBoundary from 'common/ErrorBoundary';

import user, { org } from 'admin/user';

import AsyncButton from 'ui/AsyncButton';

import { classList } from 'common/util';
import fetch, { adminFetch } from 'common/fetch';

@withRouter
export default class App extends Component {
  /*
    Following urls will be redirected to
     - http://dashboard.razorpay.com/admin#/app/entity/live/pricing_plan/8DayWD4r6ewju2
     TO
     http://dashboard.razorpay.com/admin/entity/live/pricing_plan/8DayWD4r6ewju2
     - http://dashboard.razorpay.com/admin#/app/merchants/9FI02gqNcWhPxy/detail
     TO
     http://dashboard.razorpay.com/admin/merchants/9FI02gqNcWhPxy/detail
  */
  componentWillUpdate() {
    this.legacyUrlSupport();
  }

  componentWillMount() {
    this.legacyUrlSupport();
  }

  legacyUrlSupport() {
    let hashUrl = this.props.location.hash;
    if (hashUrl && hashUrl.indexOf('#/') > -1) {
      hashUrl = hashUrl.substring(2); // Remove '#/'
      hashUrl = hashUrl.replace('app', ''); // Remove 'app'
      this.props.history.replace(hashUrl);
    }
  }

  handleLogout = () => {
    return fetch({
      url: '/admin/user/logout',
    }).then(r => {
      window.location.reload();
    });
  };

  getFPView() {
    const matchView = matchFullPageView(this.props.location.pathname);

    if (matchView && matchView.match) {
      return {
        component: matchView.component,
        props: matchView.match.params,
        sidebar: matchView.sidebar,
      };
    }
  }

  render() {
    const FPView = this.getFPView();

    return (
      <div
        class={classList(
          'app-container',
          FPView && `${FPView.component.display_name}-container`
        )}
      >
        <main>
          <ErrorBoundary resetOnProps location={this.props.location}>
            {FPView ? (
              <FPView.component {...FPView.props} />
            ) : (
              <MainContent {...this.props} />
            )}
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
        {do {
          if (FPView) {
            if (FPView.sidebar) {
              <FPView.sidebar />;
            }
          } else {
            <MainSidebar />;
          }
        }}
        <ModalContainer />
      </div>
    );
  }
}
