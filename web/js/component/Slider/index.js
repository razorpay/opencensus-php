import { connect } from 'react-redux';

import { classList } from 'common/util';

@connect(state => ({
  isMobileResolution: state.app.isMobileResolution,
}))
export default class Slider extends React.Component {
  constructor(props) {
    super();

    this.state = {
      active: props.active || 0,
    };
  }

  componentWillReceiveProps(nextProps) {
    if (nextProps.active !== this.props.active) {
      this.goTo(nextProps.active);
    }
  }

  prev = () => {
    this.goTo(this.state.active - 1);
  };

  next = () => {
    this.goTo(this.state.active + 1);
  };

  goTo = index => {
    // TODO: Check if index if within range
    this.setState({
      active: Number(index),
    });

    this.props.onSlideChange && this.props.onSlideChange(Number(index));
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
      totalSlidesNo: totalSlidesNo,
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

    const data = this.getChildProp(SlideChildrenList.length);

    let isCurrentSlideShown = false;

    return (
      <div
        class={classList(
          'Slider',
          this.props.isMobileResolution && 'Slider-mobile'
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
        onClick={goTo ? _ => goTo(idx) : undefined}
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
