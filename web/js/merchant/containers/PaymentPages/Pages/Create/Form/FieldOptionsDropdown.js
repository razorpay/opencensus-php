import Dropdown, { DropdownTrigger, DropdownContent } from 'rzp/ui/Dropdown';
import { classList } from 'common/util';

export default class FieldOptionsDropdown extends React.PureComponent {
  render() {
    const { children, type, trigger } = this.props;

    return (
      <div
        class={classList(
          'OptionsDropdown FieldOptionsDropdown',
          type && 'FieldOptionsDropdown--' + type
        )}
      >
        <Dropdown>
          <DropdownTrigger>{trigger}</DropdownTrigger>

          <DropdownContent>
            <ul class="dropdown-menu nav nav-stacked OptionsDropdown-list">
              <div className="OptionsDropdown-title">More Options</div>
              {children}
            </ul>
          </DropdownContent>
        </Dropdown>
      </div>
    );
  }
}

export const OptionsItem = ({ children, isSelected }) => (
  <li
    class={classList(
      'OptionsDropdown-item',
      isSelected && 'OptionsDropdown-item--selected'
    )}
  >
    {children}
    <i className="i i-check" />
  </li>
);
