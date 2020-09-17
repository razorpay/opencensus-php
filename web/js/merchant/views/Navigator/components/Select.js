import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';
import { Fragment } from 'react';
import Input, { Description, Label } from 'common/new-ui/Input';
import { titleCase, deepClone } from 'common/utils/rzp-utils';
import ClickOutside from './ClickOutside';
import Field from 'common/new-ui/Input';
@connect((state) => {
  return state;
})
export default class Select extends React.Component {
  state = { value: '', selected: {}, show_list: false, inp_value: '' };
  tick = `https://cdn.razorpay.com/static/assets/razorpayx/payout-links/tick.svg`;
  render() {
    var selected = {};
    if (this.props.selected && this.props.selected.length) {
      this.props.selected.forEach((s) => {
        selected[s.id] = s;
      });
    }
    const VALUE = this.props.selected ? this.props.selected.map((s) => s.name) : null;
    return (
      <ClickOutside
        onClickOutside={() => {
          this.setState({ show_list: false });
        }}
      >
        <div className="input-select-container">
          <input
            readOnly
            type="text"
            size="half_big"
            class={`Input--vTop form-control ${this.props.class ? this.props.class : ''}`}
            name="select-input"
            value={VALUE}
            onFocus={() => this.setState({ show_list: true })}
            placeholder={this.props.placeholder}
          />
          <i className="select-chev i i-chevron-down" />
          {this.state.show_list && (
            <div className="input-select">
              <ul class="unlisted">
                {this.props.options.map((o, index) => {
                  return (
                    <li
                      key={index}
                      onClick={() => {
                        if (!this.props.multiple) {
                          selected = { [o.id]: o };
                        } else {
                          if (selected[o.id]) {
                            delete selected[o.id];
                          } else {
                            selected[o.id] = o;
                          }
                        }
                        const value = Object.keys(selected).map((key) => selected[key]);
                        this.props.select(value);
                      }}
                    >
                      <div>
                        <div className="row">
                          <div className="col-xs-10">
                            <b>{titleCase(o.name)}</b>
                          </div>
                          <div className="col-xs-2">
                            {selected[o.id] ? <i className="i i-tick select-tick" /> : null}
                          </div>
                        </div>
                      </div>
                      {o.description ? <div class="sub-p">{o.description}</div> : null}
                    </li>
                  );
                })}
              </ul>
            </div>
          )}
        </div>
      </ClickOutside>
    );
  }
}
