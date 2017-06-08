import { Component } from 'react';
import AsyncButton from 'react-async-button';

export default class ListGroupToggler extends Component {
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
      <div class="list-group-item no-flex">
        <span>{this.props.label}</span>
        <AsyncButton
          class="btn btn-xs btn-default pull-right"
          text="Show/Hide"
          pendingText="Fetching..."
          onClick={this.toggle}
        />
        {this.state.show
          ? <div class="panel-body">
              <div class="list-group detail-row-container">
                {this.props.children}
              </div>
            </div>
          : null}
      </div>
    );
  }
}

ListGroupToggler.defaultProps = {
  show: false,
};
