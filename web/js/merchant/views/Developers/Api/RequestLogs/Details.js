import React, { Component } from 'react';
import { connect } from 'react-redux';
import SyntaxHighlighter from 'react-syntax-highlighter';
import arta from 'react-syntax-highlighter/dist/esm/styles/hljs/arta';

import EntityDetailRow from 'merchant/components/EntityDetailRow';
import * as ApiLogsActions from 'merchant/reducers/developers/apiLogs';
import RequestResponseDetails from 'merchant/views/Developers/components/RequestResponseDetails';
import StatusLabel from 'merchant/views/Developers/components/StatusLabel';
import {
  trackApiLogDetailsOpened,
  trackApiLogRequestResponseDetailsOpened,
} from 'merchant/views/Developers/events';

const syntaxHighlighterCustomStyles = {
  background: '#1E222E',
};

const defaultApiLog = {
  request_id: '',
  request: {
    method: '',
    url: '',
    header: {},
    body: {},
  },
  response: {
    http_status_code: '',
    header: {},
    body: {},
  },
};

class RequestDetails extends Component {
  componentDidMount() {
    trackApiLogDetailsOpened();
  }

  handleAccordionOpen(menuOpened) {
    trackApiLogRequestResponseDetailsOpened(menuOpened);
  }

  render() {
    const { id, items: apiLogs } = this.props;
    const apiLog = apiLogs.find((log) => log.request_id === id) || defaultApiLog;

    return (
      <div className="request-details-log">
        <div className="content-wrapper content-sm txn-details">
          <div className="panel panel-default">
            <div className="panel-heading">
              <p>API Request: {apiLog.request_id}</p>
            </div>
            <div className="panel-body">
              <div className="list-group details-row-container">
                <EntityDetailRow
                  label="Method and Endpoint"
                  pairClass="method-url-row"
                  title={apiLog.request.url}
                  value={() => (
                    <>
                      <span>{apiLog.request.method}</span>
                      <span>{apiLog.request.url.split('/v1')[1]}</span>
                    </>
                  )}
                />
                <EntityDetailRow
                  label="Request URL"
                  pairClass="description"
                  value={apiLog.request.url}
                  title={apiLog.request.url}
                />
                <EntityDetailRow
                  label="Status"
                  pairClass="description"
                  value={() => <StatusLabel statusCode={apiLog.response.http_status_code} />}
                />
                <hr />
                <div className="request-response-data">
                  <p>Details</p>
                  <RequestResponseDetails
                    title="Request Headers"
                    textToCopy={apiLog.request.header}
                    onOpen={() => this.handleAccordionOpen('Request Headers')}
                  >
                    <SyntaxHighlighter
                      language="json"
                      style={arta}
                      customStyle={syntaxHighlighterCustomStyles}
                    >
                      {JSON.stringify(apiLog.request.header, null, 2)}
                    </SyntaxHighlighter>
                  </RequestResponseDetails>
                  <RequestResponseDetails
                    title="Request"
                    textToCopy={apiLog.request.body}
                    onOpen={() => this.handleAccordionOpen('Request Body')}
                  >
                    <SyntaxHighlighter
                      language="json"
                      style={arta}
                      customStyle={syntaxHighlighterCustomStyles}
                    >
                      {JSON.stringify(apiLog.request.body, null, 2)}
                    </SyntaxHighlighter>
                  </RequestResponseDetails>
                  <RequestResponseDetails
                    title="Response Headers"
                    textToCopy={apiLog.response.header}
                    onOpen={() => this.handleAccordionOpen('Response Headers')}
                  >
                    <SyntaxHighlighter
                      language="json"
                      style={arta}
                      customStyle={syntaxHighlighterCustomStyles}
                    >
                      {JSON.stringify(apiLog.response.header, null, 2)}
                    </SyntaxHighlighter>
                  </RequestResponseDetails>
                  <RequestResponseDetails
                    title="Response"
                    textToCopy={apiLog.response.body}
                    onOpen={() => this.handleAccordionOpen('Response Body')}
                  >
                    <SyntaxHighlighter
                      language="json"
                      style={arta}
                      customStyle={syntaxHighlighterCustomStyles}
                    >
                      {JSON.stringify(apiLog.response.body, null, 2)}
                    </SyntaxHighlighter>
                  </RequestResponseDetails>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

export default connect((state) => ({ ...state.apiLogs }), {
  ...ApiLogsActions,
})(RequestDetails);
