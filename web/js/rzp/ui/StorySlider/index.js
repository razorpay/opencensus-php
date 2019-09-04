import { classList } from 'common/util';
import { TimedProgressBar } from 'rzp/ui/ProgressBar';

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
    this.storiesMeta = [];

    this.props.children.forEach(child => {
      const { children, ...restProps } = child.props;

      this.storiesMeta.push({
        ...restProps,
      });
    });
  }

  componentDidMount() {
    this.timer = window.setInterval(() => {
      this.goNext();
    }, this.storiesMeta[this.state.curStoryIndex].duration);
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
      storiesMeta: this.storiesMeta,
      curStoryIndex: curStoryIndex,
      goTo: this.goTo,
    };

    return (
      <div class="Stories">
        {BeforeFrame && <BeforeFrame {...frameProps} />}

        <div class="Stories-frame">{this.props.children[curStoryIndex]}</div>

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
    const { className, curStoryIndex, storiesMeta, TabComponent } = this.props;

    const TabComp = TabComponent || TimedSliderTab,
      isActive = idx === curStoryIndex;

    return (
      <div
        class={classList(
          'StoriesTabs',
          className && 'StoriesTabs--' + className
        )}
      >
        {storiesMeta.map((current, idx) => (
          <TabComp
            key={idx}
            isActive={isActive}
            duration={current.duration}
            onClick={this.onClick}
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
    class={classList(
      'StoriesTab',
      className && 'StoriesTab--' + className,
      isActive && 'active'
    )}
    onClick={onClick}
  >
    {isActive && <TimedProgressBar max={duration} />}

    {children}
  </div>
);

export const TimedSlide = ele => <div class="Story">{ele.children}</div>;
