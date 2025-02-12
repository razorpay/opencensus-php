import React, { Component } from 'react';
import { classList } from 'common/utils/rzp-utils';

const OTP_LENGTH = 6;
const DigitField = ({ pos, digit, currentIndex, setCurPos, handleInput }) => {
  return (
    <input
      name=""
      type="number"
      pattern="[0-9]*"
      className="form-control input-sm"
      value={digit[pos]}
      key={pos}
      onChange={() => {}}
      onClick={() => {
        setCurPos(pos);
      }}
      onKeyDown={(e) => {
        handleInput(pos, e);
      }}
      ref={(input) => input && currentIndex == pos && input.focus()}
    />
  );
};
export class OtpInput extends Component {
  state = {
    currentIndex: 1,
    digit: this.props.otp
      ? this.props.otp
          .split('')
          .reduce((o, val, i) => Object.assign(o, { [parseInt(i) + 1]: val }), {})
      : {
          1: '',
          2: '',
          3: '',
          4: '',
          5: '',
          6: '',
        },
  };

  setCurPos = (i) => {
    this.setState(() => {
      return {
        currentIndex: parseInt(i),
      };
    });
  };

  handleInput = (i, e) => {
    i = parseInt(i);
    let val = e.key;
    let back = 0;
    if (isNaN(val) && e.keyCode !== 8) {
      return;
    } else if (e.keyCode === 8) {
      val = '';
      if (this.state.digit[i] === '') {
        back = 1;
      }
    }
    this.setState(
      (prevState) => {
        return {
          digit: {
            ...prevState.digit,
            [i]: val.length <= 1 ? val : prevState.digit[i],
          },
          currentIndex: val.length === 1 && i + 1 <= OTP_LENGTH ? i + 1 : i - back,
        };
      },
      () => {
        const otp = Object.keys(this.state.digit)
          .map((i) => {
            return this.state.digit[i];
          })
          .join('');
        if (otp.length === OTP_LENGTH) {
          this.props.onComplete(otp);
        }
        this.props.onChange(otp);
      },
    );
  };

  render() {
    const { currentIndex } = { ...this.state };
    const { wrong, wrongOtpText = 'Wrong OTP' } = { ...this.props };
    const opt = [
      { cList: ['first'], key: 1 },
      { cList: ['middle-man'], key: 2 },
      { cList: ['last'], key: 3 },
      '-',
      { cList: ['first'], key: 4 },
      { cList: ['middle-man'], key: 5 },
      { cList: ['last'], key: 6 },
    ];

    return (
      <div>
        <strong className="">Enter the code</strong>
        {wrong && <span className="pull-right wrong-msg">{wrongOtpText}</span>}
        <div className="otp-input">
          {opt.map((i) => {
            if (i === '-') {
              return (
                <div className="seprator" key="seprator">
                  <div className="_dash" />
                </div>
              );
            } else {
              return (
                <div
                  key={i.key}
                  className={classList(
                    ...i.cList,
                    currentIndex == i.key ? 'active' : '',
                    wrong ? 'wrong' : '',
                  )}
                >
                  <DigitField
                    {...this.state}
                    pos={i.key}
                    setCurPos={this.setCurPos}
                    handleInput={this.handleInput}
                  />
                </div>
              );
            }
          })}
        </div>
      </div>
    );
  }
}

export default OtpInput;
