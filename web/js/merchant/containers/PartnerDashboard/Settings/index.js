import { Component } from 'react';
import { connect } from 'react-redux';

import { fetchPartnerApplication } from 'merchant/modules/applications';

import PartnerCredentials from './PartnerCredentials';
import WebhookDetails from './WebhookDetails';

@connect(null, { fetchPartnerApplication })
export default class SettingsContainer extends Component {
  componentWillMount() {
    this.props.fetchPartnerApplication();
  }

  render() {
    return (
      <div class="content-wrapper content-sm">
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
      </div>
    );
  }
}
