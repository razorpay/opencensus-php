import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';
import Header from 'rzp/ui/Header';
import Spinner from 'rzp/ui/Spinner';
import Time from 'rzp/ui/Time';
import * as ApplicationActions from 'merchant/modules/applications';
import * as NotificationActions from 'rzp/modules/notifications';
import * as ModalActions from 'rzp/modules/modals';


@connect(
  state => {
    return {
      user: state.session.user,
      applications: state.applications,
    };
  },
  { ...ApplicationActions, ...NotificationActions, ...ModalActions}
)
export default class ApplicationContainer extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  componentWillMount() {
    this.props.fetchApplications()
    this.props.fetchConnectedApplications()
  }

  deleteApp(application) {
    this.context.confirm({
      message: `Are you sure to delete the application? ${application.name}`,
      affirmativeLabel: 'Delete',
      affirmativePendingLabel: 'Deleting...',
      action: () =>
        this.props
          .deleteApplication(application.id)
          .then(response => {
            this.props.showNotification({
              type: 'success',
              message: 'Application deleted successfully',
            });
          })
          .catch(err => {
            this.props.showNotification({
              type: 'error',
              message: err.errors,
            });
          }),
    });
  };

  render() {
    // let { config, features, loading } = this.props.configState;
    let createdApps = this.props.applications.items
    let connectedApps = this.props.applications.connectedApps

    return (
      <div class="application-index-page">
        <div class="content-box">
          <div class="content-header">
            <strong>Connected Applications</strong>
          </div>
          {connectedApps.length
            ? connectedApps.map((app) => <AppDetails data={app} key={app.id} type={"connected"} deleteApp={this.deleteApp.bind(this)}/>)
            : <NoConnectedApps />}
          <div class="clearfix"></div>  
        </div>
        <div class="content-box">
          <div class="content-header">
            <strong>Created Applications</strong>
          </div>
          <div class="text-center content-body">
            <NewAppLink />
            {createdApps.map((app) => 
              <AppDetails data={app} key={app.id} deleteApp={this.deleteApp.bind(this)}/>
            )}
            <div class="clearfix"></div>
          </div>
        </div>
      </div>
    );
  }
}

function AppDetails (props) {
  const app = props.data;
  const isConnected = props.type === "connected"
  const Comp = isConnected ? 'div' : NavLink
  return (<div class={`application-details-container col-lg-6`}>
            <Comp class="application-details-inner" to={`/applications/${app.id}`}>
              <div class="btn-container pull-right">
                <button
                  onClick={(e) => {
                    e.stopPropagation();
                    e.preventDefault();
                    props.deleteApp(app)}
                  }
                  class="btn btn-default"
                >
                  <span>{isConnected ? "Revoke Access" : "Delete Application"}</span>
                </button>
              </div>
              <div class={`application-details ${isConnected ? "connected-app" : ""}`}>
                <div class="app-icon-container">
                  <img class="app-icon" src={app.logo_url || 'img/default-app-logo.svg'} alt=""/>
                </div>
                <div class="app-details-container">
                  <div class="app-name"><strong>{app.name}</strong></div>
                  <div class="app-id">App ID: {app.id}</div>
                  <div class="app-created-on">Created on: <Time value={app.created_at} format="DD MMM YYYY" /></div>
                </div>
              </div>
            </Comp>
          </div>)
}

function NewAppLink (props) {
  return (<div class=" application-details-container col-lg-6">
            <NavLink to="/applications/new" >
              <div class="new-application application-details">
                <div class="app-icon-container">
                  <img class="app-icon" src={'img/default-app-logo.svg'} alt=""/>
                </div>
                <div class="app-details-container">
                  <div class="app-name"><strong>Application Name</strong></div>
                  <div class="app-id">App ID: 0000000000001</div>
                  <div class="app-created-on">Created on: 00, 0000</div>
                </div>
                <div class="pull-right">
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

function NoConnectedApps (props) {
  return (
    <div class="text-center content-body">
      <img src="img/Illustration-noconnectedapp.svg" alt=""/>
      <div class="panel-body text-muted">No connected apps</div>
    </div>
  )
}