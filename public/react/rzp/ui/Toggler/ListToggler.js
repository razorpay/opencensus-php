import { Component } from 'react';
import AsyncButton from 'react-async-button';
import BaseToggler from 'rzp/ui/BaseToggler';

/*
 // USAGE: Check PaymentDetails
 // Eg: Refund list
*/

export default class ListToggler extends BaseToggler {
  render() {
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
                  {this.props.totalItems
                    ? <span>all <b>{this.props.totalItems} &gt;</b></span>
                    : null}
                </span>}
            </span>
          )}
        </AsyncButton>
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
