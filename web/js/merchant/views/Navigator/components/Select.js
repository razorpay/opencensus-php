import { connect } from 'react-redux';
import { Route, Switch, NavLink } from 'react-router-dom';
import { Fragment } from 'react';
import Input, { Description, Label } from 'common/new-ui/Input';
import { titleCase, deepClone } from 'common/utils/rzp-utils';
import ClickOutside from './ClickOutside';
import Field from 'common/new-ui/Input';
import { SMART_ROUTER, gatewayLogos } from './util';
import Popover, { PopoverBody } from 'common/ui/Popover';

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
    const IDS = ["upi_intent", "upi_collect", "BARB_R", "PUNB_R"];
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
                    <span>
                      <li
                        class={o.disabled ? 'disabled' : ''}
                        key={index}
                        onClick={() => {
                          if (!o.disabled) {
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
                          }
                        }}
                      >
                        <div>
                          <div className="row">
                            <div className="col-xs-10">
                              {this.props.session.user.isAddProviderEnabled &&
                                o.id != SMART_ROUTER && typeof o.id == 'string' && o.id.split("_").length === 2 &&
                                IDS.indexOf(o.id) === -1 && (
                                <div className="recommended-provider-img-block">
                                  <img src={gatewayLogos[o.id.split("_")[0]]} />
                                </div>
                              )}
                              <b class="optn-text">{titleCase(o.name)}</b>
                              {o.id === SMART_ROUTER ? (
                                <span className="recommended-provider">
                                  <span className="recommended-provider-text">RECOMMENDED</span>
                                  {!o.disabled ? (
                                    <Popover theme="dark" align="right">
                                      <PopoverBody>
                                        <div>Recommended for better success rate</div>
                                      </PopoverBody>
                                    </Popover>
                                  ) : null}
                                </span>
                              ) : null}
                            </div>
                            <div className="col-xs-2">
                              {selected[o.id] ? <i className="i i-tick select-tick" /> : null}
                            </div>
                          </div>
                        </div>
                        {o.description ? <div class="sub-p">{o.description}</div> : null}
                      </li>
                      {o.disabled ? (
                        <Popover theme="dark" align="right">
                          <PopoverBody>
                            <div>{o.disabled_message}</div>
                          </PopoverBody>
                        </Popover>
                      ) : null}
                    </span>
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
