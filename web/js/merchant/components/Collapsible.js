import { Component } from 'react';
import { classList } from 'common/util';

export default class Collapsible extends Component {
  static defaultProps = {
    defaultOpen: false,
  };

  constructor(props) {
    super();
    this.state = {
      open: !!props.defaultOpen,
    };
  }

  toggle = () => {
    this.setState({
      open: !this.state.open,
    });
  };

  render() {
    const { state, props } = this;
    return (
      <div className={classList('Collapsible', props.className)}>
        <header class="Collapsible--title" onClick={this.toggle}>
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
}
