import React from 'react';
import Dropdown, { DropdownTrigger, DropdownContent } from 'common/ui/Dropdown';
import { classList } from 'common/utils/rzp-utils';
import debounce from 'common/utils/debounce';

export default class FieldsDropdown extends React.PureComponent {
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
