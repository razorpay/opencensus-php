import { Component } from 'react';
import Dropdown, {
  DropdownTrigger,
  DropdownContent,
} from 'react-simple-dropdown';

export default class DropdownWrapper extends Component {
  handleClick = () => {
    if (this.props.closeOnClick) {
      let dropdown = this.dropdown;
      if (dropdown.isActive()) {
        dropdown.hide();
      } else {
        dropdown.show();
      }
    }
  };

  render() {
    let { closeOnMenuClick, children, ...otherProps } = this.props;

    return (
      <Dropdown
        ref={dropdown => (this.dropdown = dropdown)}
        onClick={this.handleClick}
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
