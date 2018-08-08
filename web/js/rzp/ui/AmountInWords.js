import { Component } from 'react';

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

  convertToWords = () => {
    const { nums, tens } = this;
    let { amount, currency = 'Rupees' } = this.props;
    let amountInWords;

    amount = parseFloat(amount);

    // if amount more than 9 digits return empty string
    if ((amount = amount.toString()).length > 9) return '';

    amountInWords = ('000000000' + amount)
      .substr(-9)
      .match(/^(\d{2})(\d{2})(\d{2})(\d{1})(\d{2})$/);

    if (!amountInWords) return;

    let str = '';

    str +=
      amountInWords[1] != 0
        ? (nums[Number(amountInWords[1])] ||
            tens[amountInWords[1][0]] + ' ' + nums[amountInWords[1][1]]) +
          'Crore '
        : '';

    str +=
      amountInWords[2] != 0
        ? (nums[Number(amountInWords[2])] ||
            tens[amountInWords[2][0]] + ' ' + nums[amountInWords[2][1]]) +
          'Lakh '
        : '';

    str +=
      amountInWords[3] != 0
        ? (nums[Number(amountInWords[3])] ||
            tens[amountInWords[3][0]] + ' ' + nums[amountInWords[3][1]]) +
          'Thousand '
        : '';

    str +=
      amountInWords[4] != 0
        ? (nums[Number(amountInWords[4])] ||
            tens[amountInWords[4][0]] + ' ' + nums[amountInWords[4][1]]) +
          'Hundred '
        : '';

    str +=
      amountInWords[5] != 0
        ? (str != '' ? 'and ' : '') +
          (nums[Number(amountInWords[5])] ||
            tens[amountInWords[5][0]] + ' ' + nums[amountInWords[5][1]])
        : '';

    return str + `${currency} Only`;
  };

  render() {
    return <span>{this.convertToWords()}</span>;
  }
}
