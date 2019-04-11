import { Component } from 'react';
import { Route, NavLink } from 'react-router-dom';
import TestModeBanner from 'merchantLA/containers/TestModeBanner';
import ReversalsTable from './ReversalsTable';
import Credit from './Credit';

export default class ReversalsListContainer extends Component {
  render() {
    return (
      <div>
        <tabbed-container>
          <header id="marketplace-header">
            <NavLink to="/reversals">Reversals</NavLink>
            <NavLink to="/credits">Credits</NavLink>
          </header>
          <TestModeBanner />
          <content>
            <Route
              path="/reversals"
              component={() => <ReversalsTable {...this.props} />}
            />
            <Route path="/credits" component={Credit} />
          </content>
        </tabbed-container>
      </div>
    );
  }
}
