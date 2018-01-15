import React, { Component } from 'react';

/*
   Definition: Label - value pair in a row. It can take toggleChildren to display children in next row (Check pricing plans in merchant entity)
   Example: Check MerchantEntity.js
   Props:
     Label: string / fn.
     Value: fn.
     children: children which will be toggled
*/
export default class ToggleEntityRow extends Component {
  state = {};

  constructor(props) {
    super(props);
    this.state = {
      open:
        typeof props.defaultOpen !== 'undefined' ? props.defaultOpen : false,
    };
  }

  handleRowClick = () => {
    this.setState({ open: !this.state.open });
  };

  render() {
    let { label, value, className = '', otherProps } = this.props;

    return (
      <div {...otherProps}>
        <div
          class={`row-item clickable ${className}`}
          onClick={this.handleRowClick}
        >
          {typeof label === 'function' ? (
            label()
          ) : (
            <div class="row-label">{label}</div>
          )}
          <div class="row-value">
            {typeof value !== 'undefined' ? (
              value()
            ) : (
              <i class={`i i-arrow-down ${this.state.open ? 'rotate' : ''}`} />
            )}
          </div>
        </div>
        <div class={`toggle-container ${this.state.open ? '' : 'collapsed'}`}>
          {this.state.open && (
            <div class="row-children">{this.props.children}</div>
          )}
        </div>
      </div>
    );
  }
}
