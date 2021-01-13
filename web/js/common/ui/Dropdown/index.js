import React, { Component } from 'react';
import Dropdown, { DropdownTrigger, DropdownContent } from 'react-simple-dropdown';

export default class DropdownWrapper extends Component {
  handleClick = (e) => {
    const dropdown = this.dropdown;
    if (this.props.closeOnClick) {
      if (dropdown.isActive()) {
        dropdown.hide();
      } else {
        dropdown.show();
      }
    } else if (this.props.closeButtonClass) {
      if (e.target && e.target.classList.contains(this.props.closeButtonClass)) {
        dropdown.hide();
      }
    }
  };

  render() {
    const { closeOnMenuClick, children, onShow, onHide, disabled, ...otherProps } = this.props;

    if (disabled) {
      return <Dropdown disabled>{children}</Dropdown>;
    }
    return (
      <Dropdown
        ref={(dropdown) => (this.dropdown = dropdown)}
        onClick={this.handleClick}
        onShow={onShow}
        onHide={onHide}
      >
        {children}
      </Dropdown>
    );
  }
}

DropdownWrapper.defaultProps = {
  closeOnClick: true,
};

export { DropdownTrigger, DropdownContent };
