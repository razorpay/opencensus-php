import React from 'react';

import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import BottomSheet from 'common/components/BottomSheet';

import { classList } from 'common/utils/rzp-utils';
import debounce from 'common/utils/debounce';
import { isMobileDevice } from 'merchant/components/Home/data';

export class FieldsDropdown extends React.PureComponent {
  state = { selectedLabel: this.props.selectedLabel };

  onSelect = (option) => {
    if (this.props.onSelect) {
      this.props.onSelect(option);
    }
  };

  _setOnHoverOption(option) {
    this.setState({
      hoverOption: option,
    });
  }

  setOnHoverOption = debounce(this._setOnHoverOption, 50);

  render() {
    const { selectedLabel, hoverOption } = this.state;
    const { options, type, beforeOptionsTxt, trigger, showInfo } = this.props;

    return (
      <div
        class={classList(
          'OptionsDropdown FieldsDropdown',
          type && `FieldsDropdown--${type}`,
          showInfo && 'FieldsDropdown--withInfo',
        )}
      >
        <Dropdown>
          <DropdownTrigger>{trigger}</DropdownTrigger>

          <DropdownContent>
            <ul class="dropdown-menu nav nav-stacked OptionsDropdown-list">
              {!!beforeOptionsTxt && <div class="OptionsDropdown-title">{beforeOptionsTxt}</div>}
              {options.map((option, ix) => {
                return (
                  <li
                    key={ix}
                    class={classList(
                      'OptionsDropdown-item',
                      selectedLabel === option.label && 'OptionsDropdown-item--selected',
                    )}
                    onClick={() => this.onSelect(option)}
                    onMouseOver={showInfo ? () => this.setOnHoverOption(option) : undefined}
                    onMouseLeave={showInfo ? () => this.setOnHoverOption(null) : undefined}
                  >
                    <i class={classList('i', option.icon && `i-${option.icon}`)} />
                    <span>{option.label}</span>
                    {option.short_description && (
                      <>
                        <br />
                        <span className="OptionsDropdown-item--short-description">
                          {option.short_description}
                        </span>
                      </>
                    )}
                    <i class="i i-check" />
                  </li>
                );
              })}
            </ul>
            {showInfo && hoverOption && (
              <div class="info">
                <img src={hoverOption.info.img} width="176" />
                <div class="title">{hoverOption.info.title}</div>
                <div class="description">{hoverOption.info.description}</div>
              </div>
            )}
          </DropdownContent>
        </Dropdown>
      </div>
    );
  }
}

/* 
  - created a separate component because state for open close to be handled for bottom sheet
  - Also not showing preview in bottom sheet
*/
export class FieldsDropdownMobile extends React.PureComponent {
  state = {
    isOpen: false,
  };

  onDismiss = () => {
    this.setState({
      isOpen: false,
    });
  };

  onSelect = (option) => {
    if (this.props.onSelect) {
      this.props.onSelect(option);
    }

    this.onDismiss();
  };

  render() {
    const { trigger, options, beforeOptionsTxt } = this.props;

    const { isOpen } = this.state;

    return (
      <BottomSheet
        isOpen={isOpen}
        isControlled
        trigger={trigger}
        className="payment-pages-v3"
        onDismiss={this.onDismiss}
        onTriggerClick={() => this.setState({ isOpen: true })}
      >
        <div class="Bottom-sheet__options">
          <div class="OptionsDropdown-title">{beforeOptionsTxt}</div>
          {options.map((option, ix) => {
            return (
              <div
                key={ix}
                class={classList('OptionsDropdown-item')}
                onClick={() => this.onSelect(option)}
              >
                <i class={classList('i', option.icon && `i-${option.icon}`)} />
                <span>{option.label}</span>
                {option.short_description && (
                  <>
                    <br />
                    <span className="OptionsDropdown-item--short-description">
                      {option.short_description}
                    </span>
                  </>
                )}
                <i class="i i-check" />
              </div>
            );
          })}
        </div>
      </BottomSheet>
    );
  }
}

export default class FieldsDropdownWrapper extends React.PureComponent {
  render() {
    const {
      beforeOptionsTxt,
      options,
      trigger,
      onSelect,
      selectedOption,
      showInfo,
      type,
    } = this.props;

    if (isMobileDevice()) {
      return (
        <FieldsDropdownMobile
          options={options}
          trigger={trigger}
          onSelect={onSelect}
          beforeOptionsTxt={beforeOptionsTxt}
        />
      );
    } else {
      return (
        <FieldsDropdown
          beforeOptionsTxt={beforeOptionsTxt}
          type={type}
          options={options}
          trigger={trigger}
          onSelect={onSelect}
          selectedOption={selectedOption && selectedOption.label}
          showInfo={showInfo}
        />
      );
    }
  }
}
