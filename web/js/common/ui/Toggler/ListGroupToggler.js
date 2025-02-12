import { Component } from 'react';
import AsyncButton from 'react-async-button';
import BaseToggler from 'common/ui/Toggler/BaseToggler';

/*
 // USAGE: Check PaymentDetails
 // Eg: Card Details
*/

export default class ListGroupToggler extends BaseToggler {
  render() {
    return (
      <div className="pair-group-item">
        <div className="col-sm-4  col-xs-4 pair-label">{this.props.label}</div>

        <div className="col-sm-8  col-xs-8 pair-value">
          <AsyncButton
            className="btn btn-xs btn-default"
            text="Show/Hide"
            pendingText="Fetching..."
            onClick={this.toggle}
          />
        </div>
        {this.state.show ? (
          <section className="secondary-details col-sm-12 col-xs-12">
            {this.props.children}
          </section>
        ) : null}
      </div>
    );
  }
}
