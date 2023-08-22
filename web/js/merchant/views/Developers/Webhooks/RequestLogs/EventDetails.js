import React, { useEffect } from 'react';
import { compose } from 'redux';
import { connect } from 'react-redux';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import SyntaxHighlighter from 'react-syntax-highlighter';
import arta from 'react-syntax-highlighter/dist/esm/styles/hljs/arta';
import StatusLabel from 'merchant/views/Developers/components/StatusLabel';
import RequestResponseDetails from 'merchant/views/Developers/components/RequestResponseDetails';
import {
  trackWebhookLogDetailsOpened,
  trackWebhookLogRequestResponseDetailsOpened,
} from 'merchant/views/Developers/events';

const syntaxHighlighterCustomStyles = {
  background: '#1E222E',
};

const defaultWebhookLog = {
  request_id: '',
  request: {
    method: '',
    url: '',
    route_name: '',
    header: {},
    body: {},
  },
  response: {
    http_status_code: '',
    header: {},
    body: {},
  },
};

const RequestDetails = ({ id, items: webhookLogs }) => {
  const webhookLog = webhookLogs.find((log) => log.request_id === id) || defaultWebhookLog;

  useEffect(() => {
    trackWebhookLogDetailsOpened();
  }, []);

  const handleAccordionOpen = (menuOpened) => {
    trackWebhookLogRequestResponseDetailsOpened(menuOpened);
  };

  return (
    <div className="request-details-log">
      <div className="content-wrapper content-sm txn-details">
        <div className="panel panel-default">
          <div className="panel-heading">
            <p>Webhook details: {webhookLog.request_id}</p>
          </div>
          <div className="panel-body">
            <div className="list-group details-row-container">
              <EntityDetailRow label="Event type" value={webhookLog.request.route_name} />
              <EntityDetailRow
                label="Webhook URL"
                pairClass="description"
                value={webhookLog.request.url}
              />
              <EntityDetailRow
                label="Status"
                pairClass="description"
                value={() => <StatusLabel statusCode={webhookLog.response.http_status_code} />}
              />
              <hr />
              <div className="request-response-data">
                <p>Details</p>
                <RequestResponseDetails
                  title="Request Headers"
                  textToCopy={webhookLog.request.header}
                  onOpen={() => handleAccordionOpen('Request Headers')}
                >
                  <SyntaxHighlighter
                    language="json"
                    style={arta}
                    customStyle={syntaxHighlighterCustomStyles}
                  >
                    {JSON.stringify(webhookLog.request.header, null, 2)}
                  </SyntaxHighlighter>
                </RequestResponseDetails>
                <RequestResponseDetails
                  title="Request"
                  textToCopy={webhookLog.request.body}
                  onOpen={() => handleAccordionOpen('Request Body')}
                >
                  <SyntaxHighlighter
                    language="json"
                    style={arta}
                    customStyle={syntaxHighlighterCustomStyles}
                  >
                    {JSON.stringify(webhookLog.request.body, null, 2)}
                  </SyntaxHighlighter>
                </RequestResponseDetails>
                <RequestResponseDetails
                  title="Response Headers"
                  textToCopy={webhookLog.response.header}
                  onOpen={() => handleAccordionOpen('Response Headers')}
                >
                  <SyntaxHighlighter
                    language="json"
                    style={arta}
                    customStyle={syntaxHighlighterCustomStyles}
                  >
                    {JSON.stringify(webhookLog.response.header, null, 2)}
                  </SyntaxHighlighter>
                </RequestResponseDetails>
                <RequestResponseDetails
                  title="Response"
                  textToCopy={webhookLog.response.body}
                  onOpen={() => handleAccordionOpen('Response Headers')}
                >
                  <SyntaxHighlighter
                    language="json"
                    style={arta}
                    customStyle={syntaxHighlighterCustomStyles}
                  >
                    {JSON.stringify(webhookLog.response.body, null, 2)}
                  </SyntaxHighlighter>
                </RequestResponseDetails>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default compose(
  connect((state) => ({
    ...state.webhookLogs,
  })),
)(RequestDetails);
