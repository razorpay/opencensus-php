import { Component } from 'react';
import { classList } from 'common/utils/rzp-utils';

// eslint-disable-next-line react/no-unsafe
export default class Collapsible extends Component {
  static defaultProps = {
    defaultOpen: false,
    childrenPosition: 'bottom',
  };

  constructor(props) {
    super(props);
    this.hasOwnValue = this.checkForValue();
    this.state = {
      open: !!(this.hasOwnValue ? props.value : props.defaultOpen),
    };
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
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
      // eslint-disable-next-line react/no-access-state-in-setstate
      open: !this.state.open,
    });
  };

  renderTitle = () => {
    const title = this.props.title;
    if (typeof title === 'function') {
      return title(this.state.open);
    }
    return title;
  };

  renderBody = () => (
    <div class={classList('Collapsible--body', this.state.open && 'open')}>
      {this.props.children}
    </div>
  );

  render() {
    const { state, props } = this;
    return (
      <div className={classList('Collapsible', props.className)}>
        {props.childrenPosition === 'top' && this.renderBody()}
        <header class="Collapsible--title" onClick={this.onToggleClick}>
          <span>{this.renderTitle()}</span>
          <i class={classList(`i-arrow-${state.open ? 'up' : 'down'}`, 'pull-right')} />
        </header>
        {props.childrenPosition === 'bottom' && this.renderBody()}
      </div>
    );
  }

  // eslint-disable-next-line consistent-return
  checkForValue = () => {
    if (this.props.hasOwnProperty('value')) {
      return true;
    }
  };
}
