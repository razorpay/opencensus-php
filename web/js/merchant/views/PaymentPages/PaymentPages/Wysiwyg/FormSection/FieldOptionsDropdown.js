import React from 'react';

import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import BottomSheet from 'common/components/BottomSheet';

import { classList } from 'common/utils/rzp-utils';
import { isMobileDevice } from 'merchant/components/Home/data';

export class FieldOptionsDropdown extends React.PureComponent {
  render() {
    const { children, type, trigger } = this.props;

    return (
      <div
        className={classList(
          'OptionsDropdown FieldOptionsDropdown',
          type && `FieldOptionsDropdown--${type}`,
        )}
      >
        <Dropdown>
          <DropdownTrigger>{trigger}</DropdownTrigger>

          <DropdownContent>
            <ul className="dropdown-menu nav nav-stacked OptionsDropdown-list">
              <div className="OptionsDropdown-title">Additional Options</div>
              {children}
            </ul>
          </DropdownContent>
        </Dropdown>
      </div>
    );
  }
}

/* 
  Separate component to handle open state for bottom sheet to close on click of 
  option(behaves like a dropdown)
*/
export class FieldOptionsDropdownMobile extends React.PureComponent {
  state = { isOpen: false };

  handleDismiss = () => {
    this.setState({ isOpen: false });
  };

  render() {
    const { children, trigger } = this.props;

    // wrapping with these classes to resuse CSS
    const _trigger = (
      <div className="OptionsDropdown FieldsDropdown">
        <div className="dropdown">
          <a className="dropdown__trigger">{trigger}</a>
        </div>
      </div>
    );

    return (
      <BottomSheet
        isOpen={this.state.isOpen}
        isControlled
        trigger={_trigger}
        onDismiss={this.handleDismiss}
        onTriggerClick={() => this.setState({ isOpen: true })}
        className="payment-pages-v3"
      >
        {/* 
          Wrapped with onclick so that on select of option, the event bubbles and is caught by onclick
          and then the bottom sheet is also closed behaving like a dropdown
        */}
        <div className="Bottom-sheet__options" onClick={this.handleDismiss}>
          <div className="OptionsDropdown-title">Additional Options</div>
          {children}
        </div>
      </BottomSheet>
    );
  }
}

export const OptionsItem = ({ children, isSelected }) => (
  <li
    className={classList('OptionsDropdown-item', isSelected && 'OptionsDropdown-item--selected')}
    data-testid={`list-option${isSelected ? '-selected' : ''}`}
  >
    {children}
    <i className="i i-check" data-testid={`tick-icon${isSelected ? '-visible' : ''}`} />
  </li>
);

export default class FieldOptionsDropdownWrapper extends React.PureComponent {
  render() {
    if (isMobileDevice()) {
      return (
        <FieldOptionsDropdownMobile trigger={this.props.trigger}>
          {this.props.children}
        </FieldOptionsDropdownMobile>
      );
    } else {
      return (
        <FieldOptionsDropdown trigger={this.props.trigger}>
          {this.props.children}
        </FieldOptionsDropdown>
      );
    }
  }
}
