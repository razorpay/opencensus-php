import Input, { Description, Label } from 'common/new-ui/Input';
import { classList } from 'common/utils/rzp-utils';

export default class SelectBox extends React.Component {
  static defaultProps = {
    onClick: function() {},
  };

  constructor(props) {
    super();

    this.state = {
      checked: !!props.defaultChecked,
    };
  }

  get isControlled() {
    return typeof this.props.checked !== 'undefined';
  }

  onChange = () => {
    if (this.isControlled) {
      this.props.onClick(!this.props.checked);

      return;
    }

    this.setState(
      {
        checked: !this.state.checked,
      },
      function() {
        this.props.onClick(this.state.checked);
      }
    );
  };

  render() {
    const { props } = this;

    const otherProps = {};

    if (props.disabled) {
      otherProps.disabled = props.disabled;
    }

    const checked = this.isControlled ? props.checked : this.state.checked;

    return (
      <div
        class={classList('SelectBox', props.className, checked && 'checked')}
        {...otherProps}
      >
        <div class="SelectBox-content">
          <Label text={props.label} />

          {props.children}

          <Description text={props.description} />
        </div>

        <div class="SelectBox-action">
          <Input.Check
            name={props.name}
            checked={checked}
            onBlur={props.onBlur}
            onChange={this.onChange}
            autoRender
          />
        </div>
      </div>
    );
  }
}
