import { Component } from 'react';
import AsyncButton from 'react-async-button';
import BaseToggler from 'rzp/ui/Toggler/BaseToggler';

/*
 // USAGE: Check PaymentDetails
 // Eg: Refund list
*/

export default class ListToggler extends BaseToggler {
  render() {
    let subText = null;
    let statusMsg = null;

    if (this.props.limit && this.props.totalItems) {
      subText = <span>items <b>&gt;</b></span>;
    } else if (this.props.totalItems) {
      subText = <span>all <b>{this.props.totalItems} &gt;</b></span>;
    }

    if (this.state.show && this.props.limit && this.props.totalItems) {
      statusMsg = (
        <span class="text-muted clearix" style={{ float: 'right' }}>
          Showing {this.props.limit} out of {this.props.totalItems}
        </span>
      );
    }

    return (
      <div class="list-table">
        <span class="list-label">{this.props.label} • </span>
        <AsyncButton
          class="primary-link"
          text="View all"
          pendingText="Fetching..."
          onClick={this.toggle}
        >
          {({ buttonText, isPending }) => (
            <span>
              {isPending && 'Fetching...'}
              {!isPending &&
                <span>
                  {this.state.show ? 'Hide' : 'View'} {' '}
                  {subText}
                </span>}
            </span>
          )}
        </AsyncButton>
        {statusMsg}
        {this.state.show
          ? <div class="panel-body" style={{ padding: '15px 0' }}>
              <div class="list-group detail-row-container">
                {this.props.children}
              </div>
            </div>
          : null}
      </div>
    );
  }
}
