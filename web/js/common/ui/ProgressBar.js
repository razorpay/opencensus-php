import { classList } from 'common/utils/rzp-utils';

export const ProgressBar = ({ value, min, max, type, className, color }) => {
  let completionPercentage = `${100 / (max - min) * value}%`;

  const style = {
    width: completionPercentage,
    ...(!!color && { backgroundColor: color }),
  };

  return (
    <div className={`progress ${className}`}>
      <div className={`progress-bar progress-bar-${type}`} style={style}>
        <span className="sr-only">
          {completionPercentage} Complete ({type})
        </span>
      </div>
    </div>
  );
};

ProgressBar.defaultProps = {
  min: 0,
  type: 'success',
  className: '',
};

export default ProgressBar;

export class TimedProgressBar extends React.PureComponent {
  static defaultProps = {
    type: 'success',
  };

  constructor(props) {
    super(props);

    this.progressBarRef = React.createRef();

    this.state = {
      width: 0,
    };
  }

  componentDidMount() {
    const node = this.progressBarRef.current;

    this.setState({
      width: node.offsetWidth,
    });
  }

  render() {
    const { width } = this.state,
      { type, className, children, duration } = this.props;

    const style = {
      width,
      transition: `width ${duration}s`,
    };

    return (
      <div
        ref={this.progressBarRef}
        className={classList('progress', 'timed-progress', className)}
      >
        <div className={`progress-bar progress-bar-${type}`} style={style}>
          {children}
        </div>
      </div>
    );
  }
}
