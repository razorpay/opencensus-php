import { Route, NavLink } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';

import Profile from 'merchant/views/Account/Profile';
import AddFunds from 'merchant/views/Account/AddFunds';
import Credits from 'merchant/views/Account/Credits/List';
import ManageTeam from 'merchant/views/Account/ManageTeam';
import Referrals from 'merchant/views/Account/Referrals/List';

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

          <ShowWhen featureEnabled="Referral">
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
          <Route path="/team" component={ManageTeam} />
        </content>
      </tabbed-container>
    </React.Fragment>
  );
}
