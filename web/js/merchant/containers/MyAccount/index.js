import { Route, NavLink } from 'react-router-dom';
import ShowWhen from 'merchant/components/ShowWhen';

import Profile from 'merchant/containers/Profile';
import AddFunds from 'merchant/containers/AddFunds';
import Credits from 'merchant/containers/Credits/List';
import Referrals from 'merchant/containers/Referrals/List';
import TeamManagement from 'merchant/containers/Team';
import { EarlySettlementAnnouncement } from 'merchant/components/Announcements';

export default function MyAccount() {
  return (
    <React.Fragment>
      <EarlySettlementAnnouncement from="MyAccount" />

      <tabbed-container>
        <header id="myaccount-header">
          <NavLink to="/profile">Profile</NavLink>

          <ShowWhen notMyRole="sellerapp agent support">
            <NavLink to="/credits">Credits</NavLink>
          </ShowWhen>

          <ShowWhen notMyRole="sellerapp agent">
            <NavLink to="/addfunds">Add Funds</NavLink>
          </ShowWhen>

          <ShowWhen
            notMyRole="sellerapp agent"
            featureEnabled="Referral"
            additionalCondition={user =>
              !user.isPartner() || user.isPartner('pure_platform')
            }
          >
            <NavLink to="/referrals">Referrals</NavLink>
          </ShowWhen>

          <ShowWhen myRole="owner">
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
