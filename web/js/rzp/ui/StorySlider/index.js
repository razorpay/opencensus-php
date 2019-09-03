import { classList } from 'common/util';
import { TimedProgressBar } from 'rzp/ui/ProgressBar';

export default class StorySlider extends React.PureComponent {
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
    this.setState(({ curStoryIndex }) => ({
      curStoryIndex: (curStoryIndex + 1) % this.props.children.length,
    }));
  };

  isValidIndex = idx => {
    if (!idx) return;

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

export class StoriesTabs extends React.PureComponent {
  render() {
    const {
      className,
      storiesMeta,
      TabComponent,
      curStoryIndex,
      goTo,
    } = this.props;

    const TabComp = TabComponent || StoryTab;

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
            isActive={idx === curStoryIndex}
            onClick={() => goTo(idx)}
            duration={current.duration}
          >
            {current.title}
          </TabComp>
        ))}
      </div>
    );
  }
}

const StoryTab = ({ className, children, onClick, isActive, duration }) => (
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

export const Story = ele => <div class="Story">{ele.children}</div>;
