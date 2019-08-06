import React, { Component } from 'react';
import { classList } from 'common/util';

const DigitField = ({ pos, digit, currentIndex, setCurPos, handleInput }) => {
  return (
    <input
      name=""
      class="form-control input-sm"
      value={digit[pos]}
      onClick={() => {
        setCurPos(pos);
      }}
      onKeyDown={e => {
        handleInput(pos, e);
      }}
      ref={input => input && currentIndex == pos && input.focus()}
    />
  );
};
export class OtpInput extends Component {
  state = {
    currentIndex: 1,
    wrong: this.props.wrong,
    digit: {
      1: '',
      2: '',
      3: '',
      4: '',
      5: '',
      6: '',
    },
  };
  setCurPos = i => {
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
      prevState => {
        return {
          digit: {
            ...prevState.digit,
            [i]: val.length <= 1 ? val : prevState.digit[i],
          },
          currentIndex: val.length === 1 && i + 1 <= 6 ? i + 1 : i - back,
        };
      },
      () => {
        const otp = Object.keys(this.state.digit)
          .map(i => {
            return this.state.digit[i];
          })
          .join('');
        if (otp.length === 6) {
          this.props.onComplete(otp);
        }
      }
    );
  };

  render() {
    const { currentIndex, wrong } = { ...this.state };
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
        <strong class="">Enter the code</strong>
        {wrong && <span class="pull-right wrong-msg">Wrong OTP</span>}
        <div class="otp-input">
          {opt.map(i => {
            if (i === '-') {
              return (
                <div class="seprator">
                  <div class="_dash"></div>
                </div>
              );
            } else {
              return (
                <div
                  class={classList(
                    ...i.cList,
                    currentIndex == i.key ? 'active' : '',
                    wrong ? 'wrong' : ''
                  )}
                >
                  <DigitField
                    {...this.state}
                    pos={i.key}
                    setCurPos={this.setCurPos}
                    handleInput={this.handleInput}
                  ></DigitField>
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
