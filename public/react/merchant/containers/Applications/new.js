import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import Header from 'rzp/ui/Header';
import Spinner from 'rzp/ui/Spinner';
import * as ApplicationActions from 'merchant/modules/config';
import * as NotificationActions from 'rzp/modules/notifications';

@connect(
  state => {
    return {
      user: state.session.user,
    };
  },
  { ...ApplicationActions, ...NotificationActions }
)
export default class ApplicationContainer extends Component {
  componentWillMount() {
    // this.props.fetchApplications
  }

  render() {
    // let { config, features, loading } = this.props.configState;

    return (

      <div class="content-wrapper">
        <div class="text-center content-wrapper">
          <div className="panel-body text-muted">New App</div>
        </div>
      </div>
    );
  }
}
