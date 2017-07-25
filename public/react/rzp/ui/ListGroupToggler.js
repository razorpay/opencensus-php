import { Component } from 'react';
import AsyncButton from 'react-async-button';

/*
 // USAGE: Check PaymentDetails
 // Eg: Card Details
 */

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
      <div class="pair-group-item">
        <div class="col-sm-4 pair-label">{this.props.label}</div>

        <div class="col-sm-8 pair-value">
          <AsyncButton
            class="btn btn-xs btn-default"
            text="Show/Hide"
            pendingText="Fetching..."
            onClick={this.toggle}
          />
        </div>
        {this.state.show && <div class="quote-vertical" />}
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
