import { Children, cloneElement, Component } from 'react';
import Dropdown, {
  DropdownTrigger,
  DropdownContent,
} from 'react-simple-dropdown';
import MenuItem from './MenuItem';
import Spinner from 'rzp/ui/Spinner';
import './Dropdown.styl';

export default class DropdownButton extends Component {
  constructor() {
    super(...arguments);
    this.state = {
      loading: false,
    };
    this.handleMenuItemClick = ::this.handleMenuItemClick;
    this.bindMenuItem = ::this.bindMenuItem;
  }

  handleMenuItemClick() {
    this.refs.dropdown.hide();
  }

  toggleLoading() {
    this.setState({
      loading: !this.state.loading,
    });
  }

  bindMenuItem(child) {
    if (child.type === MenuItem) {
      const originalOnClick = child.props.onClick;
      child = cloneElement(child, {
        onClick: event => {
          this.handleMenuItemClick(event);
          if (originalOnClick) {
            const returnFn = originalOnClick.apply(child, arguments);
            if (returnFn && typeof returnFn.then === 'function') {
              this.toggleLoading();
              returnFn
                .then(() => {
                  this.toggleLoading();
                })
                .catch(() => {
                  this.toggleLoading();
                });
            }
          }
        },
      });
    }
    return child;
  }

  render() {
    let loading = this.state.loading;
    let { title, children, className, btnTriggerClass } = this.props;

    return (
      <Dropdown class={`btn-group ${className}`} ref="dropdown">
        <DropdownTrigger class={`btn ${btnTriggerClass}`} disabled={loading}>
          {loading && <Spinner />}
          <span class="title">{title}</span>
          <span class="caret" />
        </DropdownTrigger>
        <DropdownContent class="dropdown-menu">
          {Children.map(children, this.bindMenuItem)}
        </DropdownContent>
      </Dropdown>
    );
  }
}
