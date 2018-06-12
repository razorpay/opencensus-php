import { Component } from 'react';

export default class AccordianSection extends Component {
  render() {
    const { index, children, isOpen, onChange } = this.props;
    const className = isOpen ? ' open' : '';

    return (
      <div class={`Accordian-item ${className}`}>
        <header class={`${className}`} data-id={index} onClick={onChange}>
          Step {index + 1}
        </header>
        <div class={`Accordian-content ${className}`}>{children}</div>
      </div>
    );
  }
}
