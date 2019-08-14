import Dropdown, { DropdownTrigger, DropdownContent } from 'rzp/ui/Dropdown';
import { classList } from 'common/util';

export default class FieldsDropdown extends React.PureComponent {
  state = { selectedLabel: this.props.selectedLabel };

  onSelect = option => {
    console.log('SELECTED...', option);
    this.props.onSelect && this.props.onSelect(option);
  };

  render() {
    const { selectedLabel } = this.state;
    const { options, type, beforeOptionsTxt, trigger } = this.props;

    return (
      <div
        class={classList(
          'OptionsDropdown FieldsDropdown',
          type && 'FieldsDropdown--' + type
        )}
      >
        <Dropdown>
          <DropdownTrigger>{trigger}</DropdownTrigger>

          <DropdownContent>
            <ul class="dropdown-menu nav nav-stacked OptionsDropdown-list">
              {!!beforeOptionsTxt && (
                <div className="OptionsDropdown-title">{beforeOptionsTxt}</div>
              )}
              {options.map((option, ix) => {
                return (
                  <li
                    key={ix}
                    class={classList(
                      'OptionsDropdown-item',
                      selectedLabel === option.label &&
                        'OptionsDropdown-item--selected'
                    )}
                    onClick={_ => this.onSelect(option)}
                  >
                    <i
                      class={classList('i', option.icon && 'i-' + option.icon)}
                    />
                    <span>{option.label}</span>
                    <i class="i i-check" />
                  </li>
                );
              })}
            </ul>
          </DropdownContent>
        </Dropdown>
      </div>
    );
  }
}
