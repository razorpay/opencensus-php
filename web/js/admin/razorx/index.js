import { Route, Redirect, Switch } from 'react-router-dom';
import { ShowWhenRoute } from 'admin/components/ShowWhen';
import { org } from 'admin/user';

import MainNavLink from 'admin/components/MainNavLink';
import Features from './Features';
import Experiments from './Experiments';

const links = [
  // title, url, permission, icon
  ['Experiments', '/razorx/experiments', '', 'date'],
  ['Features', '/razorx/features', '', 'layers'],
];

export const Sidebar = () => (
  <aside className={`org-${org.custom_code}`}>
    <a
      id="org-logo"
      href="/razorx"
      style={{ backgroundImage: `url("${org.main_logo_url}")` }}
    />
    {links.map((l, i) => (
      <div key={i}>
        <MainNavLink to={l[1]} permission={l[2]} icon={l[3]}>
          {l[0]}
        </MainNavLink>
      </div>
    ))}
  </aside>
);

export default class RazorX extends React.Component {
  render() {
    return (
      <Switch>
        <Route path="/razorx/experiments" component={Experiments} />
        <ShowWhenRoute path="/razorx/features" component={Features} />
        <Redirect to="/razorx/experiments" />
      </Switch>
    );
  }
}
