import React, { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';

import { fetchPartnerApplication } from 'merchant/reducers/applications';
import { openModal, closeModal } from 'merchant_common/reducers/modals';

import Spinner from 'common/ui/Spinner';
import DetailRow from 'merchant/components/DetailRow';

import ManageWebhook from './ManageWebhook';
import ViewCredentials from './ViewCredentials';

import { trackSettingsEvents } from 'merchant/views/PartnerDashboard/ga';
import ShowWhen from 'merchant/components/ShowWhen';

@connect(
  (state) => ({
    isLoading: state.applications.loading,
    application: state.applications.partnerApplication,
  }),
  {
    fetchPartnerApplication,
    closeModal,
    openModal,
  },
)
export default class SettingsContainer extends Component {
  componentDidMount() {
    this.props.fetchPartnerApplication();
    trackSettingsEvents();
  }

  handleManageWebhookClick = (mode) => () => {
    this.props.openModal({
      component: (
        <ManageWebhook
          onSave={this.props.closeModal}
          appId={this.props.application.id}
          mode={mode}
        />
      ),
    });
  };

  handleViewCredentialsClick = (mode) => () => {
    const type = mode === 'test' ? 'dev' : 'prod';
    const { clientCredentials } = this.props.application;
    this.props.openModal({
      component: <ViewCredentials mode={mode} credentials={clientCredentials[type]} />,
    });
  };

  render() {
    const { isLoading } = this.props;
    return (
      <tabbed-container>
        <header>
          <NavLink end to="/partners/settings">
            Settings
          </NavLink>
          <ShowWhen additionalCondition={(user) => user.isPartnershipForPhantomEnabled}>
            <NavLink end to="/partners/config">
              Configuration
            </NavLink>
          </ShowWhen>
        </header>
        <content>
          <div className="content-wrapper content-sm partner-settings">
            {isLoading ? (
              <div className="page-spinner-container">
                <Spinner />
              </div>
            ) : (
              <>
                <div className="panel panel-default">
                  <div className="panel-heading">Webhook</div>
                  <div className="list-group details-row-container partner-settings--webhook-details">
                    {/* live webhook */}
                    <DetailRow
                      label="Live Webhook"
                      value={() => (
                        <button
                          onClick={this.handleManageWebhookClick('live')}
                          className="btn btn-link"
                        >
                          Manage
                        </button>
                      )}
                    />

                    {/* test webhook */}
                    <DetailRow
                      label="Test Webhook"
                      value={() => (
                        <button
                          onClick={this.handleManageWebhookClick('test')}
                          className="btn btn-link"
                        >
                          Manage
                        </button>
                      )}
                    />
                  </div>
                </div>

                <div className="panel panel-default">
                  <div className="panel-heading">Partner Credentials</div>
                  <div className="list-group details-row-container">
                    {/* live credentials */}
                    <DetailRow
                      label="Live Credentials"
                      value={() => (
                        <button
                          onClick={this.handleViewCredentialsClick('live')}
                          className="btn-link"
                        >
                          View
                        </button>
                      )}
                    />

                    {/* test credentials */}
                    <DetailRow
                      label="Test Credentials"
                      value={() => (
                        <button
                          onClick={this.handleViewCredentialsClick('test')}
                          className="btn-link"
                        >
                          View
                        </button>
                      )}
                    />
                  </div>
                </div>
              </>
            )}
          </div>
        </content>
      </tabbed-container>
    );
  }
}
