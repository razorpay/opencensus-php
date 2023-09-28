import { connect } from 'react-redux';
import React from 'react';

@connect((state) => {
  return state;
})
export default class Provider extends React.Component {
  render() {
    return (
      <React.Fragment>
        <div class="row gateway-row">
          <div style={{ display: 'flex' }}>
            <div class="provider-img-holder">
              <img class="w100" src={this.props.provider.image_url} alt="" />
            </div>
            <div style={{ width: '70%', padding: '10px' }}>
              <h3 class="">{this.props.provider.id.toUpperCase()}</h3>
              <div>
                <p>Cards, NetBanking, wallet</p>
                {/* <Popover theme="dark" align="bottom" parentQuerySelector={`.Modal--large`}>
                  <PopoverBody>
                    <h6>Available payment method</h6>
                    <div>
                      <div>Cards</div>
                      <div>Netbanking</div>
                      <div>Cards</div>
                      <div>Wallets(Freecharge, OLA Money, Mobikwik)</div>
                      <div>UPI</div>
                    </div>
                  </PopoverBody>
                </Popover> */}
              </div>
            </div>
          </div>
        </div>
      </React.Fragment>
    );
  }
}
