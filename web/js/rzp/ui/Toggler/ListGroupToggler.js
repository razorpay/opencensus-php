import { Component } from 'react';
import AsyncButton from 'react-async-button';
import BaseToggler from 'rzp/ui/Toggler/BaseToggler';

/*
 // USAGE: Check PaymentDetails
 // Eg: Card Details
*/

export default class ListGroupToggler extends BaseToggler {
  render() {
    return (
      <div class="pair-group-item">
        <div class="col-sm-4  col-xs-4 pair-label">{this.props.label}</div>

        <div class="col-sm-8  col-xs-8 pair-value">
          <AsyncButton
            class="btn btn-xs btn-default"
            text="Show/Hide"
            pendingText="Fetching..."
            onClick={this.toggle}
          />
        </div>
        {this.state.show ? (
          <section class="secondary-details col-sm-12 col-xs-12">
            {this.props.children}
          </section>
        ) : null}
      </div>
    );
  }
}
