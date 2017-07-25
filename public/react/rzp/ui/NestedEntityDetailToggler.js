import { Component } from 'react';
import AsyncButton from 'react-async-button';
import EntityDetailRow from 'merchant/components/EntityDetailRow';

/*
 // Description: To be used where Key has array of objects and each object has key-value pair
 // USAGE: Check NestedEntityDetailRow
 // Eg: Notes and Acquirer Data
*/

export default class NestedEntityDetailToggler extends Component {
  constructor() {
    super(...arguments);
    this.state = {
      show: false,
    };
    this.toggle = ::this.toggle;
  }

  componentWillMount() {
    this.setState({ show: this.props.show });
  }

  toggle() {
    this.setState({
      show: !this.state.show,
    });

    if (!this.state.show && this.props.onToggleClick) {
      return this.props.onToggleClick();
    }
  }

  render() {
    return (
      <EntityDetailRow
        label={this.props.label}
        value={() => (
          <div>
            <AsyncButton
              class="btn btn-xs btn-default"
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

NestedEntityDetailToggler.defaultProps = {
  show: false,
};
