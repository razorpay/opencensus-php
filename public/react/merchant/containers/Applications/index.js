import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import Header from 'rzp/ui/Header';
import Spinner from 'rzp/ui/Spinner';
import Time from 'rzp/ui/Time';
import * as ApplicationActions from 'merchant/modules/applications';
import * as NotificationActions from 'rzp/modules/notifications';

@connect(
  state => {
    return {
      user: state.session.user,
      applications: state.applications
    };
  },
  { ...ApplicationActions, ...NotificationActions }
)
export default class ApplicationContainer extends Component {
  componentWillMount() {
    this.props.fetchApplications()
  }

  render() {
    // let { config, features, loading } = this.props.configState;
    let createdApps = this.props.applications.items
    return (
      <div class="application-index-page">
        <div class="content-box">
          <div class="content-header">
            <strong>Connected Applications</strong>
          </div>
          <div class="text-center content-body">
            <img src="img/Illustration-noconnectedapp.svg" alt=""/>
            <div class="panel-body text-muted">No connected apps</div>
          </div>
        </div>
        <div class="content-box">
          <div class="content-header">
            <strong>Created Applications</strong>
          </div>
          <div class="text-center content-body">
            <NewAppLink />
            {createdApps.map((app) => 
              <AppDetails data={app} key={app.id}/>
            )}
            <div class="clearfix"></div>
          </div>
        </div>
      </div>
    );
  }
}

function AppDetails (props) {
  let app = props.data;
  return (<div class=" application-details-container col-lg-6">
            <NavLink to={`/applications/${app.id}`}>
              <div className="btn-container pull-right">
                <button
                  class="btn btn-default"
                >
                  <span>Delete Application</span>
                </button>
              </div>
              <div className="application-details">
                <div className="app-icon-container">
                  <img class="app-icon" src={app.logo_url || 'img/default-app-logo.svg'} alt=""/>
                </div>
                <div className="app-details-container">
                  <div className="app-name"><strong>{app.name}</strong></div>
                  <div className="app-id">App ID: {app.id}</div>
                  <div className="app-created-on">Created on: <Time value={app.created_at} format="DD MMM YYYY" /></div>
                </div>
              </div>
            </NavLink>
          </div>)
}

function NewAppLink (props) {
  return (<div class=" application-details-container col-lg-6">
            <NavLink to="/applications/new" >
              <div class="new-application application-details">
                <div className="app-icon-container">
                  <img class="app-icon" src={'img/default-app-logo.svg'} alt=""/>
                </div>
                <div className="app-details-container">
                  <div className="app-name"><strong>Application Name</strong></div>
                  <div className="app-id">App ID: 0000000000001</div>
                  <div className="app-created-on">Created on: 00, 0000</div>
                </div>
                <div className="pull-right">
                  <button
                    class="btn btn-primary"
                  >
                    <span>Create Application</span>
                  </button>
                </div>
              </div>
            </NavLink>
          </div>)
}