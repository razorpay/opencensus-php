import React from 'react';
import { connect } from 'react-redux';

class Provider extends React.Component {
  render() {
    return (
      <div className="row gateway-row">
        <div style={{ display: 'flex' }}>
          <div className="provider-img-holder">
            <img className="w100" src={this.props.provider.image_url} alt="" />
          </div>
          <div style={{ width: '70%', padding: '10px' }}>
            <h3 className="">{this.props.provider.id.toUpperCase()}</h3>
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
    );
  }
}

export default connect((state) => {
  return state;
})(Provider);
