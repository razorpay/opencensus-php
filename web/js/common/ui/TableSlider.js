import React, { Component } from 'react';
import Table from 'common/ui/Table/Index';

/**
 * Component: Slide table from rigth to left using "margin-left" property instead of scroll
 * Prop: Sliding Unit by which table should slide in both direction
 *
 * Pass all the usual props you pass for Table components
 *
 */

//- Constants
const DEFAULT_TAB_WIDTH = 30;
const DEFAULT_SLIDE_UNIT = 100;

export default class TableSlider extends Component {
  gutter = 0;
  slideUnit = this.props.slideUnit || DEFAULT_SLIDE_UNIT;
  state = {
    currentMargin: 0,
    styles: null,
  };

  componentDidMount() {
    const sliderWidth = document.querySelector('.table-slider').offsetWidth;
    const targetWidth = document.querySelector('.table-slider table')
      .offsetWidth;
    this.gutter = targetWidth - sliderWidth;
  }

  handleSlideClick = direction => {
    if (direction && direction.length) {
      let { slideUnit } = this.props;

      let marginProp = '';

      let newState = {
        styles: {},
        currentMargin: this.state.currentMargin,
      };

      if (direction === 'right') {
        newState.currentMargin = newState.currentMargin - this.slideUnit;
        //- Check whether the slider has reached max of right, if so then give max gutter margin-left
        if (newState.currentMargin <= -this.gutter) {
          newState.currentMargin = -this.gutter;
        }
      } else {
        newState.currentMargin = newState.currentMargin + this.slideUnit;
        //- Check whether the slider has reached the max of left, if so then give 0 margin-left
        if (newState.currentMargin >= 0) {
          newState.currentMargin = 0;
        }
      }

      newState.styles['marginLeft'] = `${newState.currentMargin}px`;

      this.setState(newState);
    }
  };

  render() {
    let { children } = this.props;
    let newProps = { ...this.props };

    delete newProps.children;
    delete newProps.slideUnit;

    return (
      <div className="table-slider">
        {this.state.currentMargin !== 0 && (
          <button
            className="slider-tabs btn-default left-btn"
            onClick={() => this.handleSlideClick('left')}
          >
            <i className="i i-arrow-back" />
          </button>
        )}

        <Table tableStyle={this.state.styles} {...newProps} />

        {this.state.currentMargin !== -this.gutter && (
          <button
            className="slider-tabs btn-default right-btn"
            onClick={() => this.handleSlideClick('right')}
          >
            <i className="i i-arrow-forward" />
          </button>
        )}
      </div>
    );
  }
}
