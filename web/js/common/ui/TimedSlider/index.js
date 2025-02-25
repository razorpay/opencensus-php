import { classList } from 'common/utils/rzp-utils';
import { TimedProgressBar } from 'common/ui/ProgressBar';
import React from 'react';

/*
  Instagram style story component
*/

export default class TimedSlider extends React.PureComponent {
  constructor(props) {
    super(props);
    const curStoryIndex = this.isValidIndex(props.defaultStoryIndex)
      ? props.defaultStoryIndex
      : 0;

    this.state = { curStoryIndex };
    this.setStoriesAndMeta();
  }

  setStoriesAndMeta() {
    this.slidersMeta = [];

    this.props.children.forEach(child => {
      const { children, ...restProps } = child.props;

      this.slidersMeta.push({
        ...restProps,
      });
    });
  }

  componentDidMount() {
    this.timer = window.setInterval(() => {
      this.goNext();
    }, this.slidersMeta[this.state.curStoryIndex].duration);
  }

  goNext = () => {
    // Reset the curStoryIndex to 0 after the last slide
    this.setState(({ curStoryIndex }) => ({
      curStoryIndex: (curStoryIndex + 1) % this.props.children.length,
    }));
  };

  isValidIndex = idx => {
    if (isNaN(idx)) {
      return;
    }

    return idx < this.props.children.length;
  };

  goTo = idx => {
    if (!this.isValidIndex(idx)) {
      return;
    }

    this.setState({
      curStoryIndex: idx,
    });
  };

  componentWillUnMount() {
    window.clearInterval(this.timer);
  }

  render() {
    const { BeforeFrame, AfterFrame } = this.props,
      { curStoryIndex } = this.state;

    const frameProps = {
      slidersMeta: this.slidersMeta,
      curStoryIndex: curStoryIndex,
      goTo: this.goTo,
    };

    return (
      <div className="TimedSlider">
        {BeforeFrame && <BeforeFrame {...frameProps} />}

        <div className="TimedSlider-frame">
          {this.props.children[curStoryIndex]}
        </div>

        {AfterFrame && <AfterFrame {...frameProps} />}
      </div>
    );
  }
}

export class TimedSliderTabs extends React.PureComponent {
  onClick = idx => () => {
    this.props.goTo(idx);
  };

  render() {
    const { className, curStoryIndex, slidersMeta, TabComponent } = this.props,
      TabComp = TabComponent || TimedSliderTab;

    return (
      <div
        className={classList(
          'TimedSliderTabs',
          className && 'TimedSliderTabs--' + className
        )}
      >
        {slidersMeta.map((current, idx) => (
          <TabComp
            key={idx}
            isActive={idx === curStoryIndex}
            duration={current.duration}
            onClick={this.onClick(idx)}
          >
            {current.title}
          </TabComp>
        ))}
      </div>
    );
  }
}

const TimedSliderTab = ({
  className,
  children,
  onClick,
  isActive,
  duration,
}) => (
  <div
    className={classList(
      'TimedSliderTab',
      className && 'TimedSliderTab--' + className,
      isActive && 'active'
    )}
    onClick={onClick}
  >
    {isActive && <TimedProgressBar max={duration} />}

    {children}
  </div>
);

export const TimedSlide = ele => <div className="TimedSlide">{ele.children}</div>;
