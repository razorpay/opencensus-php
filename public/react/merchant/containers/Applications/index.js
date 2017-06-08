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
        <div class="pull-right">
          <NavLink to="/applications/new">Create Application</NavLink>
        </div>
        <div className="clearfix"></div>
        <div class="text-center content-wrapper">
          <img src="img/Illustration-noconnectedapp.svg" alt=""/>
          <div className="panel-body text-muted">No connected apps</div>
          <button
            class="btn btn-primary"
            onClick={() => {}}
          >
            <span>Browse available apps</span>
          </button>
        </div>
      </div>
    );
  }
}
