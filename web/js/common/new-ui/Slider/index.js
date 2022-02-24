import { classList } from 'common/utils/rzp-utils';
import { isNone } from 'common/utils/rzp-utils';

export default class Slider extends React.Component {
  constructor(props) {
    super(props);

    this.TOTAL_SLIDES_LENGTH = props.children.length;

    this.state = {
      active: props.active || 0,
    };
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (nextProps.active !== this.props.active) {
      this.goTo(nextProps.active);
    }
  }

  prev = CB => this.goTo(this.state.active - 1, CB);

  next = CB => this.goTo(this.state.active + 1, CB);

  goTo = (activeNum, CB) => {
    let active = Number(activeNum);

    if (active < 0 || active > this.TOTAL_SLIDES_LENGTH) {
      return;
    }

    this.setState(
      {
        active,
      },
      () => {
        if (CB) {
          CB(this.getChildProp());
        }
      }
    );

    this.props.onSlideChange && this.props.onSlideChange(active);
  };

  getChildProp = (totalSlidesNo = this.TOTAL_SLIDES_LENGTH) => {
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
    const SlideChildrenList = this.props.children.filter(
      child => !isNone(child)
    );

    this.TOTAL_SLIDES_LENGTH = SlideChildrenList.length;

    const data = this.getChildProp(this.TOTAL_SLIDES_LENGTH);

    const { beforeSlide, afterSlide } = this.props;

    return (
      <div
        class={classList(
          'Slider',
          this.props.className && `Slider--${this.props.className}`
        )}
      >
        {beforeSlide && beforeSlide(data)}
        {SlideChildrenList[this.state.active](data)}
        {afterSlide && afterSlide(data)}
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
