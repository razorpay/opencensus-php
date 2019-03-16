import { Component } from 'react';
import { classList } from 'common/util';

export default class Collapsible extends Component {
  static defaultProps = {
    defaultOpen: false,
  };

  constructor(props) {
    super(props);
    this.hasOwnValue = this.checkForValue();
    this.state = {
      open: !!(this.hasOwnValue ? props.value : props.defaultOpen),
    };
  }

  componentWillReceiveProps(nextProps) {
    if (this.hasOwnValue) {
      this.setState({
        open: !!nextProps.value,
      });
    }
  }

  onToggleClick = (...args) => {
    if (this.hasOwnValue) {
      this.props.onToggleClick(...args);
      return;
    }
    this.toggle(...args);
  };

  toggle = () => {
    this.setState({
      open: !this.state.open,
    });
  };

  render() {
    const { state, props } = this;
    return (
      <div className={classList('Collapsible', props.className)}>
        <header class="Collapsible--title" onClick={this.onToggleClick}>
          <span>{props.title}</span>
          <i
            class={classList(
              `i-arrow-${state.open ? 'up' : 'down'}`,
              'pull-right'
            )}
          />
        </header>
        <div class={classList('Collapsible--body', state.open && 'open')}>
          {props.children}
        </div>
      </div>
    );
  }

  checkForValue = () => {
    if (this.props.hasOwnProperty('value')) {
      return true;
    }
  };
}
