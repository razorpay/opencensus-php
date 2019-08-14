import { classList } from 'common/util';

export default class Slider extends React.Component {
  constructor(props) {
    super(props);

    this.TOTAL_SLIDES_LENGTH = props.children.length;

    this.state = {
      active: props.active || 0,
    };
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.active !== this.props.active) {
      this.goTo(nextProps.active);
    }
  }

  prev = () => this.goTo(-1);

  next = () => this.goTo(1);

  goTo = index => {
    const active = Number(this.state.active + index);

    if (this.TOTAL_SLIDES_LENGTH <= active || active < 0) {
      return;
    }

    this.setState({
      active,
    });

    this.props.onSlideChange && this.props.onSlideChange(active);
  };

  getChildProp = totalSlidesNo => {
    const { active } = this.state;

    const prev = active > 0 && this.prev;
    const next = active < totalSlidesNo - 1 && this.next;

    return {
      active,
      next,
      prev,
      goTo: this.goTo,
      totalSlidesNo,
    };
  };

  render() {
    const SliderDotsIdsList = [],
      SlideChildrenList = [];

    const children = this.props.children.filter(child => {
      return child !== null && child !== undefined;
    });

    children.forEach((child, idx) => {
      const component = child({});

      if (component.type.name === 'SliderDots') {
        SliderDotsIdsList.push(idx);
      } else {
        SlideChildrenList.push(child);
      }
    });

    this.TOTAL_SLIDES_LENGTH = SlideChildrenList.length;

    const data = this.getChildProp(this.TOTAL_SLIDES_LENGTH);

    let isCurrentSlideShown = false;

    return (
      <div
        class={classList(
          'Slider',
          this.props.className && `Slider--${this.props.className}`
        )}
      >
        {children.map(child => {
          const Component = child(data);

          if (Component.type.name === 'SliderDots') {
            return Component;
          }

          const Slide = SlideChildrenList[this.state.active](data);

          if (!isCurrentSlideShown) {
            isCurrentSlideShown = true;

            return Slide;
          }

          return null;
        })}
      </div>
    );
  }
}

export const SliderDots = props => {
  const { active, totalSlidesNo, goTo } = props;

  const Dots = [];

  for (let idx = 0; idx < totalSlidesNo; idx++) {
    Dots.push(
      <div
        key={idx}
        class={classList(
          'SliderDots-dot',
          active === idx && 'SliderDots-dot--active'
        )}
        onClick={goTo ? () => goTo(idx) : undefined}
      />
    );
  }

  return (
    <div class="SliderDots">
      {Dots}

      <div>{props.children}</div>
    </div>
  );
};
