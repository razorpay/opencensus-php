import React from 'react';
import { withRouter, NavLink } from 'react-router-dom';
import Time from 'common/ui/Time';
import TableBody from 'common/ui/TableBody';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import { pluralize } from 'common/utils/rzp-utils';

class WebhooksList extends React.Component {
  render() {
    const { webhooks, isLoading, onNewWebhookClick } = this.props;

    const WebhooksListItem = ({ webhook }) => {
      const activeEventsCount = [];

      Object.keys(webhook.events).forEach((key) => {
        if (webhook.events[key] === true) {
          activeEventsCount.push(key);
        }
      });

      return (
        <EntityItemRow id={webhook.id}>
          <td>
            <NavLink to={`/webhooks/${webhook.id}`}>
              <code>{webhook.url}</code>
            </NavLink>
          </td>
          <td>
            <span
              className={`status-label label ${webhook.active ? 'label-info' : 'label-disabled'}`}
            >
              {webhook.active ? 'Enabled' : 'Disabled'}
            </span>
          </td>
          <td>
            {activeEventsCount.length}
            {pluralize(' event', activeEventsCount.length)}
          </td>
          <td>
            <Time value={webhook.updated_at} format="DD MMM YYYY, hh:mm:ss a" />
          </td>
        </EntityItemRow>
      );
    };

    return (
      <div className="table-responsive">
        <table className="table table-hover">
          <thead>
            <tr>
              <th>URL</th>
              <th>Status</th>
              <th>Events</th>
              <th>Last Updated</th>
            </tr>
          </thead>
          <TableBody
            isLoading={isLoading}
            colSpan={4}
            rows={webhooks}
            emptyTableRow={
              <tr>
                <td className="text-center empty-table" colSpan={4}>
                  <div className="empty-table-message font-size-16">
                    You have not setup any webhook
                    <div class="add-new-webhook" onClick={onNewWebhookClick}>
                      Add new Webhook
                    </div>
                  </div>
                </td>
              </tr>
            }
          >
            {webhooks
              ? webhooks.map((webhook) => <WebhooksListItem key={webhook.id} webhook={webhook} />)
              : null}
          </TableBody>
        </table>
        {!webhooks.length ? (
          <div className="text-center description">
            List of all your webhook setup will show up here.
          </div>
        ) : null}
      </div>
    );
  }
}

export default withRouter(WebhooksList);
