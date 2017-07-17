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
      <div class="row detail-row">
        <label class="col-sm-4">{this.props.label}</label>

        <div class="col-sm-8">
          <AsyncButton
            class="btn btn-xs btn-default"
            text="Show/Hide"
            pendingText="Fetching..."
            onClick={this.toggle}
          />
        </div>
        {this.state.show
          ? <section class="secondary-details col-sm-12">
              {this.props.children}
            </section>
          : null}
      </div>
    );
  }
}

ListGroupToggler.defaultProps = {
  show: false,
};
