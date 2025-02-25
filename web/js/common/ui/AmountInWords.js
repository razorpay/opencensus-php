import { Component } from 'react';
import React from 'react';

export default class AmountInWords extends Component {
  nums = [
    '',
    'One ',
    'Two ',
    'Three ',
    'Four ',
    'Five ',
    'Six ',
    'Seven ',
    'Eight ',
    'Nine ',
    'Ten ',
    'Eleven ',
    'Twelve ',
    'Thirteen ',
    'Fourteen ',
    'Fifteen ',
    'Sixteen ',
    'Seventeen ',
    'Eighteen ',
    'Nineteen ',
  ];

  tens = [
    '',
    '',
    'Twenty',
    'Thirty',
    'Forty',
    'Fifty',
    'Sixty',
    'Seventy',
    'Eighty',
    'Ninety',
  ];

  convertToWords = digits => {
    const { nums, tens } = this;
    let digitsInWords;

    digitsInWords = ('000000000' + digits)
      .substr(-9)
      .match(/^(\d{2})(\d{2})(\d{2})(\d{1})(\d{2})$/);

    if (!digitsInWords) return;

    let str = '';

    str +=
      digitsInWords[1] != 0
        ? (nums[Number(digitsInWords[1])] ||
            tens[digitsInWords[1][0]] + ' ' + nums[digitsInWords[1][1]]) +
          'Crore '
        : '';

    str +=
      digitsInWords[2] != 0
        ? (nums[Number(digitsInWords[2])] ||
            tens[digitsInWords[2][0]] + ' ' + nums[digitsInWords[2][1]]) +
          'Lakh '
        : '';

    str +=
      digitsInWords[3] != 0
        ? (nums[Number(digitsInWords[3])] ||
            tens[digitsInWords[3][0]] + ' ' + nums[digitsInWords[3][1]]) +
          'Thousand '
        : '';

    str +=
      digitsInWords[4] != 0
        ? (nums[Number(digitsInWords[4])] ||
            tens[digitsInWords[4][0]] + ' ' + nums[digitsInWords[4][1]]) +
          'Hundred '
        : '';

    str +=
      digitsInWords[5] != 0
        ? '' +
          (nums[Number(digitsInWords[5])] ||
            tens[digitsInWords[5][0]] + ' ' + nums[digitsInWords[5][1]])
        : '';

    return str;
  };

  getValue = () => {
    const { amount, currency = 'Rupees', currencyDenom = 'Paise' } = this.props;
    let amountArray;
    let str;

    // split amount into array of [`rupees`, `paise`]
    amountArray = (amount + '').split('.');

    str = `${this.convertToWords(amountArray[0])} ${currency}`;

    // check for `0` paise
    if (parseFloat(amountArray[1])) {
      str += ` and ${this.convertToWords(
        amountArray[1]
      )} ${currencyDenom} Only`;
    } else {
      str += ' Only';
    }

    return str;
  };

  render() {
    const { amount, prefix, suffix } = this.props;

    if (parseFloat(amount) === 0 || `${parseInt(amount)}`.length > 9) {
      return '';
    }

    return <span>{`${prefix} ${this.getValue()} ${suffix}`}</span>;
  }
}
