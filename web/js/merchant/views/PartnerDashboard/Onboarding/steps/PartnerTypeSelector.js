import { Description, Label } from 'common/new-ui/Input';
import { classList } from 'common/utils/rzp-utils';
import Popover, { PopoverBody, PopoverTitle } from 'common/ui/Popover';

export default class PartnerTypeSelector extends React.Component {
  static defaultProps = {
    onClick: function () {},
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
      function () {
        this.props.onClick(this.state.checked);
      },
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
      <>
      <div
        className={classList(
          'SelectBox',
          'PartnerTypeSelectBox',
          props.className,
          checked && 'checked',
        )}
        {...otherProps}
        onClick={this.onChange}
      >
        {
          !props.isMobile ? (
            <Popover align="right" theme="dark" className="SelectBox--popover">
              <PopoverTitle>
                <h4>
                  <strong>Select if you are a</strong>
                </h4>
              </PopoverTitle>
              <PopoverBody>{props.hoverContent}</PopoverBody>
            </Popover>
          ) : ''
        }
        <div className="select-box-image-container">
          <img src={props.icon} alt="" />
        </div>
        <div className="SelectBox-heading">
          <Label text={props.label} />
          <Description text={props.description} />
        </div>
        <div className="SelectBox-action">{props.children}</div>
        <div className="select-button-container">
          <div className={checked ? 'checked radio-btn' : 'radio-btn'}>
            {checked && <i className="fa fa-check"></i>}
          </div>
        </div>
      </div>
      {
        props.isMobile && checked ?
        <div className="SelectBox--information">
          <div className="who-uses">Select if you are a</div>
          <div className="content">{props.hoverContent}</div>
        </div> : ''
      }
      </>
    );
  }
}
