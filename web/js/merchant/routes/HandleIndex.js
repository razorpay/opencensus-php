import React from 'react';
import { matchFullPageView } from 'merchant/routes';
export default class HandleIndex extends React.Component {
  UNSAFE_componentWillMount() {
    const { location, history } = this.props;
    const matchView = matchFullPageView(location.pathname);
    if (matchView && matchView.match) {
      return;
    }
    if (location.hash) {
      const path = location.hash.replace(/#\/?app\/?/, '') || 'dashboard';

      const newRoute = location.pathname + path;
      history.push(newRoute);
    } else {
      history.push('/dashboard');
    }
  }

  render() {
    return null;
  }
}
