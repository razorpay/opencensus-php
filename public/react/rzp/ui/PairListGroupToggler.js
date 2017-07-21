import { Component } from 'react';
import AsyncButton from 'react-async-button';
import EntityDetailRow from 'merchant/components/EntityDetailRow';

export default class PairListGroupToggler extends Component {
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

PairListGroupToggler.defaultProps = {
  show: false,
};
