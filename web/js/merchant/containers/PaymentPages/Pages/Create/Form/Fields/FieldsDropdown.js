import Dropdown, { DropdownTrigger, DropdownContent } from 'rzp/ui/Dropdown';
import { classList } from 'common/util';

export default class UDFFieldsDropdown extends React.PureComponent {
  state = { selectedLabel: this.props.selectedLabel };

  onSelectField = option => {
    console.log('SELECTED...', option);
    this.props.onSelectField && this.props.onSelectField(option);
  };

  render() {
    const { selectedLabel } = this.state;
    const { options, type, children } = this.props;

    return (
      <div class={classList('FieldDropdown', 'FieldDropdown--' + type)}>
        <Dropdown>
          <DropdownTrigger>{children}</DropdownTrigger>

          <DropdownContent>
            <ul class="dropdown-menu nav nav-stacked FieldDropdown-list">
              <div class="FieldDropdown-title">New Input Field</div>
              {options.map((option, ix) => {
                return (
                  <li
                    key={ix}
                    class={classList(
                      'FieldDropdown-item',
                      selectedLabel === option.label &&
                        'FieldDropdown-item--selected'
                    )}
                    onClick={_ => this.onSelectField(option)}
                  >
                    <i
                      className={classList(
                        'i',
                        option.icon && 'i-' + option.icon
                      )}
                    />
                    <span className="display-label">{option.label}</span>
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
