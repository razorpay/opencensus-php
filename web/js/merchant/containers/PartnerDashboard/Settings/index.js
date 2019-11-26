import { Component } from 'react';
import { connect } from 'react-redux';
import { NavLink } from 'react-router-dom';

import { fetchPartnerApplication } from 'merchant/reducers/applications';
import { openModal, closeModal } from 'merchant_common/reducers/modals';

import Spinner from 'common/ui/Spinner';
import DetailRow from 'merchant/components/DetailRow';

import ManageWebhook from './ManageWebhook';
import ViewCredentials from './ViewCredentials';

import { trackSettingsEvents } from '../ga';

@connect(
  state => ({
    isLoading: state.applications.loading,
    application: state.applications.partnerApplication,
  }),
  {
    fetchPartnerApplication,
    closeModal,
    openModal,
  }
)
export default class SettingsContainer extends Component {
  componentWillMount() {
    this.props.fetchPartnerApplication();
  }

  componentDidMount() {
    trackSettingsEvents();
  }

  handleManageWebhookClick = mode => () => {
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

  handleViewCredentialsClick = mode => () => {
    const type = mode === 'test' ? 'dev' : 'prod';
    const { clientCredentials } = this.props.application;
    this.props.openModal({
      size: 'small',
      component: (
        <ViewCredentials mode={mode} credentials={clientCredentials[type]} />
      ),
    });
  };

  render() {
    const { isLoading } = this.props;
    return (
      <tabbed-container>
        <header>
          <NavLink exact to="/partners/settings">
            Settings
          </NavLink>
        </header>
        <content>
          <div class="content-wrapper content-sm partner-settings">
            {isLoading ? (
              <div class="page-spinner-container">
                <Spinner />
              </div>
            ) : (
              <>
                <div class="panel panel-default">
                  <div class="panel-heading">Webhook</div>
                  <div class="list-group details-row-container partner-settings--webhook-details">
                    {/* live webhook */}
                    <DetailRow
                      label="Live Webhook"
                      value={() => (
                        <button
                          onClick={this.handleManageWebhookClick('live')}
                          class="btn btn-link"
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
                          class="btn btn-link"
                        >
                          Manage
                        </button>
                      )}
                    />
                  </div>
                </div>

                <div class="panel panel-default">
                  <div class="panel-heading">Partner Credentials</div>
                  <div class="list-group details-row-container">
                    {/* live credentials */}
                    <DetailRow
                      label="Live Credentials"
                      value={() => (
                        <button
                          onClick={this.handleViewCredentialsClick('live')}
                          class="btn-link"
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
                          class="btn-link"
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
