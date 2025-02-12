import React from 'react';
import { NavLink } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import Time from 'common/ui/Time';
import DataTable from 'common/ui/Table/DataTable';
const ClickableUrl = {
  title: 'URL',
  value: (item) => {
    let url = `/webhooks/${item.id}`;
    if (window?.location?.pathname?.includes('developers')) {
      url = `/developers/webhooks/${item.id}`;
    }

    return (
      <NavLink to={url}>
        <code style={{ minWidth: '300px', whiteSpace: 'normal' }}>{item.url}</code>
      </NavLink>
    );
  },
  width: 'auto',
};

const Status = {
  title: 'Status',
  value: ({ active }) => (
    <span className={`status-label label ${active ? 'label-info' : 'label-disabled'}`}>
      {active ? 'Enabled' : 'Disabled'}
    </span>
  ),
};

const EventCount = {
  title: 'Events',
  value: (item) => {
    const activeEvents = getActiveEvents(item.events);
    return `${activeEvents} event${activeEvents > 1 ? 's' : ''}`;
  },
};

const LastUpdatedAt = {
  title: 'Last Updated',
  value: (item) => <Time value={item.updated_at} format="DD MMM YYYY, hh:mm:ss a" />,
};

class WebhooksList extends React.Component {
  renderEmptyComponent = () => {
    return (
      <div className="empty-table-message font-size-16">
        You have not setup any webhook
        <div className="add-new-webhook" onClick={this.props.onNewWebhookClick}>
          Add new Webhook
        </div>
      </div>
    );
  };

  render() {
    const { webhooks, isLoading } = this.props;
    return (
      <>
        <DataTable
          title="Webhooks"
          columns={[ClickableUrl, Status, EventCount, LastUpdatedAt]}
          count={this.props.count}
          skip={this.props.skip}
          paginate={this.props.paginate}
          items={this.props.webhooks}
          loading={this.props.isLoading}
          EmptyComponent={this.renderEmptyComponent}
          {...this.props}
        />
        {webhooks?.length === 0 && !isLoading && (
          <div className="text-center description">
            List of all your webhook setup will show up here.
          </div>
        )}
      </>
    );
  }
}

function getActiveEvents(events) {
  return Object.keys(events).filter((eventName) => events[eventName]).length;
}

export default withRouter(WebhooksList);
