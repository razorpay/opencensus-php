import { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import AppDetails, {
  AppDetailsLoader,
} from 'merchant/views/Settings/Applications/components/AppDetails';
import NewAppLink from 'merchant/views/Settings/Applications/components/NewAppLink';
import {
  NoConnectedApps,
  LoadingConnectedApps,
} from 'merchant/views/Settings/Applications/components/NoConnectedApps';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import * as NotificationActions from 'merchant_common/reducers/notifications';
import * as ModalActions from 'merchant_common/reducers/modals';
import * as ApplicationActions from 'merchant/reducers/applications';
class ApplicationContainer extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  componentDidMount() {
    this.props.user.isRevokeApplicationEnabled
      ? this.props.fetchOauthConnectedApplications()
      : this.props.fetchConnectedApplications();
    this.props.fetchApplications();
  }

  deleteApp = (application) => {
    this.context.confirm({
      message: () => (
        <span>
          Merchants mapped to this application will no longer be associated with it.
          <br />
          <br />
          Are you sure you want to delete <b>{application.name}</b>?
        </span>
      ),
      affirmativeLabel: 'Delete',
      affirmativePendingLabel: 'Deleting...',
      action: () =>
        this.props
          .deleteApplication(application.id)
          .then(() => {
            this.props.showNotification({
              type: 'success',
              message: 'Application deleted successfully',
            });
          })
          .catch((err) => {
            this.props.showNotification({
              type: 'error',
              message: err.errors,
            });
          }),
    });
  };

  revokeAccess = (token) => {
    const { isRevokeApplicationEnabled } = this.props.user;
    this.context.confirm({
      message: `Are you sure you want to revoke access to ${
        isRevokeApplicationEnabled ? token.application_name : token.application.name
      }?`,
      affirmativeLabel: 'Revoke Access',
      affirmativePendingLabel: 'Revoking Access...',
      action: () => {
        // Maintaining backward compatibility
        isRevokeApplicationEnabled
          ? this.props
              .revokeOauthApplicationAccess(token.application_id)
              .then(() => {
                this.props.showNotification({
                  type: 'success',
                  message: 'Access revoked successfully',
                });
              })
              .catch((err) => {
                this.props.showNotification({
                  type: 'error',
                  message: err.errors,
                });
              })
          : this.props
              .revokeAccess(token.id)
              .then(() => {
                this.props.showNotification({
                  type: 'success',
                  message: 'Access revoked successfully',
                });
              })
              .catch((err) => {
                this.props.showNotification({
                  type: 'error',
                  message: err.errors,
                });
              });
      },
    });
  };

  renderConnectedApplications = () => {
    const { isRevokeApplicationEnabled } = this.props.user;
    const { connectedAppsloading, tokens } = this.props.applications;
    return (
      <div class="content-box">
        <div class="content-header">
          <strong>Connected Applications</strong>
        </div>
        {connectedAppsloading ? (
          <LoadingConnectedApps />
        ) : tokens.length ? (
          tokens.map((data) => (
            <AppDetails
              data={data}
              key={isRevokeApplicationEnabled ? data.application_id : data.id}
              type="connected"
              onBtnClick={this.revokeAccess}
              isRevokeApplicationEnabled={isRevokeApplicationEnabled}
            />
          ))
        ) : (
          <NoConnectedApps />
        )}
        <div class="clearfix" />
      </div>
    );
  };

  render() {
    const { items, createdAppsloading } = this.props.applications;
    const pathname = this.props.location.pathname;

    return (
      <div class="application-index-page">
        {['/applications', ROUTES_INFO.APPLICATIONS].includes(pathname) &&
          this.renderConnectedApplications()}
        {pathname === '/partners/applications' ? (
          <div class="content-box">
            <div class="content-header">
              <strong>Created Applications</strong>
            </div>
            <div class="text-center content-body">
              <NewAppLink toNewApplication={`${pathname}/new`} />
              {items.map((data) => (
                <AppDetails
                  data={data}
                  key={data.id}
                  onBtnClick={this.deleteApp}
                  entityDetailLink={`${pathname}/${data.id}`}
                />
              ))}
              {createdAppsloading && <AppDetailsLoader />}
              <div class="clearfix" />
            </div>
          </div>
        ) : null}
      </div>
    );
  }
}

export default connect(
  (state) => {
    return {
      user: state.session.user,
      applications: state.applications,
    };
  },
  { ...ApplicationActions, ...NotificationActions, ...ModalActions },
)(ApplicationContainer);
