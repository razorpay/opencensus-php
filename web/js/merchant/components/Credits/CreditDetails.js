import { Component } from 'react';

import Amount from 'rzp/ui/Amount';
import Table from 'rzp/ui/Table/Index';

import { createdAt, creditId } from 'rzp/ui/item/pair';

export default class CreditDetails extends Component {
  state = {
    showCollapsible: false,
  };

  toggleCollapsible = () => {
    const showCollapsible = this.state.showCollapsible;

    this.setState(
      {
        showCollapsible: !showCollapsible,
      },
      () => {
        this.props.trackToggleHistory(this.props.title)(!showCollapsible);
      }
    );
  };

  render() {
    const {
      title,
      creditItems,
      totalCredits,
      description,
      onManageAlert,
    } = this.props;
    const { showCollapsible } = this.state;

    return (
      <div class="panel panel-default credit-details">
        <div class="panel-body">
          {onManageAlert && (
            <button class="btn btn-default pull-right" onClick={onManageAlert}>
              Manage Alerts
            </button>
          )}

          <strong class="big-font">{title}</strong>

          <p class="puck">{description}</p>

          <strong class="big-font">
            <Amount value={totalCredits} />
          </strong>

          {!!creditItems.length && (
            <button
              class="btn-link pull-right toggle-history"
              onClick={this.toggleCollapsible}
            >
              {!!showCollapsible ? 'Hide' : 'View'} History
              <i class={`i-chevron-${!!showCollapsible ? 'up' : 'down'}`} />
            </button>
          )}

          {showCollapsible && (
            <div class="collapsible">
              <Table
                className="history"
                rows={creditItems}
                columns={[
                  creditId,
                  {
                    title: 'Campaign',
                    value: ({ campaign }) => campaign,
                  },
                  {
                    title: 'Value',
                    value: ({ value }) => <Amount value={value} />,
                  },
                  createdAt,
                ]}
              />
            </div>
          )}
        </div>
      </div>
    );
  }
}
