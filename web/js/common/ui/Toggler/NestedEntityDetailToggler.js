import { Component } from 'react';
import AsyncButton from 'react-async-button';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import BaseToggler from 'common/ui/Toggler/BaseToggler';
/*
 // Description: To be used where Key has array of objects and each object has key-value pair
 // USAGE: Check NestedEntityDetailRow
 // Eg: Notes and Acquirer Data
*/

export default class NestedEntityDetailToggler extends BaseToggler {
  render() {
    return (
      <EntityDetailRow
        label={this.props.label}
        value={() => (
          <div>
            <AsyncButton
              className="btn btn-xs btn-default"
              text="Show/Hide"
              pendingText="Fetching..."
              onClick={this.toggle}
            />
            {this.state.show ? this.props.children : null}
          </div>
        )}
      />
    );
  }
}
