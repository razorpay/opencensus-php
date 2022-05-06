import React from 'react';
import { connect } from 'react-redux';
import ClickOutside from './ClickOutside';
import { SMART_ROUTER, gatewayLogos } from './util';
import Popover, { PopoverBody } from 'common/ui/Popover';

@connect((state) => {
  return state;
})
export default class Select extends React.Component {
  state = { show_list: false };
  tick = `https://cdn.razorpay.com/static/assets/razorpayx/payout-links/tick.svg`;
  render() {
    const { show_list } = this.state;
    const {
      selected: props_selected,
      class: custom_class,
      placeholder,
      options,
      multiple,
      select,
    } = this.props;

    let selected = {};
    if (props_selected?.length > 0) {
      props_selected.forEach((option, index, obj) => {
        selected[option.id] = option;
        if (option.disabled) {
          obj.splice(index, 1);
        }
      });
    }
    const VALUE = props_selected?.map((option) => option.name) || null;
    const IDS = ['upi_intent', 'upi_collect', 'BARB_R', 'PUNB_R'];
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
            className={`Input--vTop form-control${custom_class ? ` ${custom_class}` : ''}`}
            name="select-input"
            value={VALUE}
            onFocus={() => this.setState({ show_list: true })}
            placeholder={placeholder}
          />
          <i className="select-chev i i-chevron-down" />
          {show_list && (
            <div className="input-select">
              <ul className="unlisted">
                {options?.map((option, index) => {
                  let gateway;
                  if (typeof option?.id === 'string' && IDS.indexOf(option?.id) === -1) {
                    const id = option?.id?.split('_');
                    if (option?.id === 'razorpay') {
                      gateway = id[0];
                    } else {
                      id.pop();
                      gateway = id.join('_');
                    }
                  }
                  return (
                    <span key={index}>
                      <li
                        className={option.disabled ? 'disabled' : ''}
                        key={index}
                        onClick={() => {
                          if (!option.disabled) {
                            if (!multiple) {
                              selected = { [option.id]: option };
                            } else if (selected[option.id]) {
                              delete selected[option.id];
                            } else {
                              selected[option.id] = option;
                            }
                            const value = Object.keys(selected).map((key) => selected[key]);
                            select(value);
                          }
                        }}
                      >
                        <div>
                          <div className="row">
                            <div className="col-xs-10">
                              {option?.id != SMART_ROUTER && gateway && (
                                <div className="recommended-provider-img-block">
                                  <img src={gatewayLogos[gateway]} alt={gateway} />
                                </div>
                              )}
                              <b className="optn-text">{option.name}</b>
                              {option.id === SMART_ROUTER ? (
                                <span className="recommended-provider">
                                  <span className="recommended-provider-text">RECOMMENDED</span>
                                  {!option.disabled ? (
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
                              {selected[option.id] ? <i className="i i-tick select-tick" /> : null}
                            </div>
                          </div>
                        </div>
                        {option.description ? (
                          <div className="sub-p">{option.description}</div>
                        ) : null}
                      </li>
                      {option.disabled ? (
                        <Popover theme="dark" align="right">
                          <PopoverBody>
                            <div>{option.disabled_message}</div>
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
