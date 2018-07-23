import PartnerCredentials from './PartnerCredentials';
import WebhookDetails from './WebhookDetails';

export default () => (
  <div class="content-wrapper content-sm">
    <div class="panel panel-default">
      <div class="panel-heading">Webhook</div>
      <WebhookDetails />
    </div>

    <div class="panel panel-default">
      <div class="panel-heading">Partner Credentials</div>

      <PartnerCredentials />
    </div>
  </div>
);
