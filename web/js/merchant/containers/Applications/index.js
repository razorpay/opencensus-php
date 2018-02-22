import { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import AppDetails, {
  AppDetailsLoader,
} from 'merchant/components/Applications/AppDetails';
import NewAppLink from 'merchant/components/Applications/NewAppLink';
import {
  NoConnectedApps,
  LoadingConnectedApps,
} from 'merchant/components/Applications/NoConnectedApps';
import * as NotificationActions from 'rzp/modules/notifications';
import * as ModalActions from 'rzp/modules/modals';
import * as ApplicationActions from 'merchant/modules/applications';

@connect(
  state => {
    return {
      user: state.session.user,
      applications: state.applications,
    };
  },
  { ...ApplicationActions, ...NotificationActions, ...ModalActions }
)
export default class ApplicationContainer extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  componentWillMount() {
    this.props.fetchApplications();
    this.props.fetchConnectedApplications();
  }

  deleteApp = application => {
    this.context.confirm({
      message: `Are you sure you want to delete ${application.name}?`,
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

  revokeAccess = token => {
    this.context.confirm({
      message: `Are you sure you want to revoke access to ${
        token.application.name
      }?`,
      affirmativeLabel: 'Revoke Access',
      affirmativePendingLabel: 'Revoking Access...',
      action: () =>
        this.props
          .revokeAccess(token.id)
          .then(response => {
            this.props.showNotification({
              type: 'success',
              message: 'Access revoked successfully',
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
    let createdApps = this.props.applications.items;
    let isCreatedAppsLoading = this.props.applications.createdAppsloading;
    let isConnectedAppsLoading = this.props.applications.connectedAppsloading;
    let tokens = this.props.applications.tokens;

    return (
      <div class="application-index-page">
        <div class="content-box">
          <div class="content-header">
            <strong>Connected Applications</strong>
          </div>
          {isConnectedAppsLoading ? (
            <LoadingConnectedApps />
          ) : tokens.length ? (
            tokens.map(data => (
              <AppDetails
                data={data}
                key={data.id}
                type={'connected'}
                onBtnClick={this.revokeAccess}
              />
            ))
          ) : (
            <NoConnectedApps />
          )}
          <div class="clearfix" />
        </div>
        <div class="content-box">
          <div class="content-header">
            <strong>Created Applications</strong>
          </div>
          <div class="text-center content-body">
            <NewAppLink />
            {createdApps.map(data => (
              <AppDetails
                data={data}
                key={data.id}
                onBtnClick={this.deleteApp}
              />
            ))}
            {isCreatedAppsLoading && <AppDetailsLoader />}
            <div class="clearfix" />
          </div>
        </div>
      </div>
    );
  }
}
