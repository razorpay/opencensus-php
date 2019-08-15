import { classList } from 'common/util';
import { TimedProgressBar } from 'rzp/ui/ProgressBar';

export default class Stories extends React.PureComponent {
  constructor(props) {
    super(props);
    const curStoryIndex = this.isValidIndex(props.defaultStoryIndex)
      ? props.defaultStoryIndex
      : 0;

    this.state = { curStoryIndex };
    this.setStoriesAndMeta();
  }

  setStoriesAndMeta() {
    this.stories = [];
    this.storiesMeta = [];

    this.props.children.forEach(child => {
      const { children, ...restProps } = child.props;

      this.stories.push(<div class="Story">{children}</div>);

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
    this.setState({
      curStoryIndex:
        (this.state.curStoryIndex + 1) % this.props.children.length,
    });
  };

  isValidIndex = idx => {
    return idx && idx < this.props.children.length;
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
    const { beforeFrame: BeforeFrame, afterFrame: AfterFrame } = this.props;
    const { curStoryIndex } = this.state;

    return (
      <div class="Stories">
        {BeforeFrame && (
          <BeforeFrame
            stories={this.stories}
            curStoryIndex={curStoryIndex}
            goTo={this.goTo}
          />
        )}

        <div class="Stories-frame">{this.stories[curStoryIndex]}</div>

        <br />
        {AfterFrame && (
          <AfterFrame
            storiesMeta={this.storiesMeta}
            curStoryIndex={curStoryIndex}
            goTo={this.goTo}
          />
        )}
      </div>
    );
  }
}

export class StoriesTabs extends React.PureComponent {
  render() {
    const {
      className,
      storiesMeta,
      tabComponent,
      curStoryIndex,
      goTo,
    } = this.props;

    const TabComp = tabComponent || Tab;

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
            showLoader={idx === curStoryIndex}
            onClick={() => goTo(idx)}
            duration={current.duration}
          >
            {idx} {current.title}
          </TabComp>
        ))}
      </div>
    );
  }
}

const Tab = ({ children, onClick, showLoader, duration }) => (
  <div class="StoriesTab" onClick={onClick}>
    {showLoader && <TimedProgressBar type="success" max={duration} />}

    {children}
  </div>
);

export const Story = props => props.children;
