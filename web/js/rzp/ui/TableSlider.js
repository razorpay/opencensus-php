import React, { Component } from 'react';

export default class TableSlider extends Component {
  gutter = 0;
  slideUnit = this.props.slideUnit || 30;

  state = {
    currentPosition: 0,
  };

  componentWillMount() {
    const tabWidth = 100; //Two(left/right) tab has 50px width
    const targetWidth = document.querySelector(this.props.target).offsetWidth;
    const sliderWidth = document.querySelector('.table-slider').offsetWidth;

    this.gutter = targetWidth - tabWidth - sliderWidth;
  }

  handleSlideClick = direction => {
    switch (direction) {
    // case 'left':
    //   if()
    //   return;
    // case 'right':
    //   if()
    //   return;
    // case ed
    }
  };

  render() {
    let { children } = this.props;

    return (
      <div class="table-slider">
        <span class="left-tab slider-tabs">
          <i class="i i-arrow-back" />
        </span>
        {children}
        <span class="right-tab slider-tabs">
          <i class="i i-arrow-forward" />
        </span>
      </div>
    );
  }
}
