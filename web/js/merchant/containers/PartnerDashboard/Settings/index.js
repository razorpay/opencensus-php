import { Component, Fragment } from 'react';
import { connect } from 'react-redux';

import { fetchPartnerApplication } from 'merchant/modules/applications';
import { openModal, closeModal } from 'rzp/modules/modals';

import Spinner from 'rzp/ui/Spinner';
import DetailRow from 'merchant/components/DetailRow';

import WebhookDetails from './WebhookDetails';
import ViewCredentials from './ViewCredentials';

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
      <div class="content-wrapper content-sm partner-settings">
        {isLoading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <Fragment>
            <div class="panel panel-default">
              <div class="panel-heading">Webhook</div>
              <WebhookDetails />
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
          </Fragment>
        )}
      </div>
    );
  }
}
