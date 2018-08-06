import { Component, Fragment } from 'react';
import { connect } from 'react-redux';

import { fetchPartnerApplication } from 'merchant/modules/applications';

import Spinner from 'rzp/ui/Spinner';

import PartnerCredentials from './PartnerCredentials';
import WebhookDetails from './WebhookDetails';

@connect(
  state => ({
    isLoading: state.applications.loading,
  }),
  { fetchPartnerApplication }
)
export default class SettingsContainer extends Component {
  componentWillMount() {
    this.props.fetchPartnerApplication();
  }

  render() {
    const { isLoading } = this.props;
    return (
      <div class="content-wrapper content-sm">
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
              <PartnerCredentials
                clientCredentials={this.props.clientCredentials}
              />
            </div>
          </Fragment>
        )}
      </div>
    );
  }
}
