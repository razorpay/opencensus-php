import { Route, NavLink } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';

import Profile from 'merchant/containers/Profile';
import AddFunds from 'merchant/containers/AddFunds';
import Credits from 'merchant/containers/Credits/List';
import Referrals from 'merchant/containers/Referrals/List';
import TeamManagement from 'merchant/containers/Team';

export default function MyAccount() {
  return (
    <React.Fragment>
      <tabbed-container>
        <header id="myaccount-header">
          <ShowWhen additionalCondition={user => user.isAllowedView('profile')}>
            <NavLink to="/profile">Profile</NavLink>
          </ShowWhen>

          <ShowWhen additionalCondition={user => user.isAllowedView('credits')}>
            <NavLink to="/credits">Credits</NavLink>
          </ShowWhen>

          <ShowWhen
            additionalCondition={user => user.isAllowedView('add_funds')}
          >
            <NavLink to="/addfunds">Add Funds</NavLink>
          </ShowWhen>

          <ShowWhen
            featureEnabled="Referral"
            additionalCondition={user =>
              user.isAllowedView('referrals') &&
              (!user.isPartner() || user.isPartner('pure_platform'))
            }
          >
            <NavLink to="/referrals">Referrals</NavLink>
          </ShowWhen>

          <ShowWhen additionalCondition={user => user.isAllowedTeamManagement}>
            <NavLink to="/team">Manage Team</NavLink>
          </ShowWhen>
        </header>
        <content>
          <Route path="/profile" component={Profile} />
          <Route path="/credits" component={Credits} />
          <Route path="/addfunds" component={AddFunds} />
          <Route path="/referrals" component={Referrals} />
          <Route path="/team" component={TeamManagement} />
        </content>
      </tabbed-container>
    </React.Fragment>
  );
}
