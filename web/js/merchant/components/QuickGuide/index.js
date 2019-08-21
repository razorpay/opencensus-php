import { connect } from 'react-redux';

import {
  getQuickGuideLocalStorageKey,
  getQuickGuideIsClosedFromLocalStorage,
  setQuickGuideIsClosedInLocalStorage,
} from './utils';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/modules/onboarding';

/*
  DATA_POINTS: contains keys to get data from redux store
  FEATURE: Product name
*/

export default params => {
  const { feature: FEATURE, data_points: DATA_POINTS } = params;

  let _WrappedComponent;

  @connect(
    state => {
      let newState = {};

      DATA_POINTS.forEach(key => {
        newState[key] = state[key];
      });

      return {
        ...newState,
        user: state.session.user,
        mode: state.session.mode,
        currentOnboarding: getCurrentProductOnBoardingDetails(state, FEATURE),
      };
    },
    { handleProductQuickGuide }
  )
  class QuickGuideHOC extends React.PureComponent {
    constructor(props) {
      super(props);

      this.state = {};

      if (props.currentOnboarding.isTour) {
        const newState = this.getInitState();

        this.state = {
          ...newState,
        };
      }
    }

    generateDataPointFromProps = type => {
      const latestEle = this.props[type].items[0] || {};

      return {
        [type]: {
          type: type,
          lastItemId: latestEle.id,
          items: [],
          loading: false,
        },
      };
    };

    getInitState = () => {
      return DATA_POINTS.reduce((accumulator, type) => {
        const data = this.generateDataPointFromProps(type);

        return {
          ...accumulator,
          [type]: data,
        };
      }, {});
    };

    componentWillReceiveProps(nextProps) {
      if (!this.props.currentOnboarding.isTour) return;

      let isLoading = false;

      const dataPointsFromPropsList = DATA_POINTS.map(type => {
        return nextProps[type];
      });

      dataPointsFromPropsList.forEach(dataPoint => {
        if (dataPoint.loading) {
          isLoading = true;

          return false;
        }
      });

      if (isLoading) return;

      const newState = {};

      DATA_POINTS.forEach(type => {
        const stateDataPoint = this.state[type],
          propDataPoint = nextProps[type].items[0] || {};

        if (
          stateDataPoint.lastItemId &&
          propDataPoint.id !== stateDataPoint.lastItemId
        ) {
          newState[type] = {
            ...stateDataPoint,
            items: [propDataPoint],
          };
        }
      });

      this.setState({
        ...newState,
      });
    }

    onClickClose = () => {
      setQuickGuideIsClosedInLocalStorage(FEATURE);

      this.props.handleProductQuickGuide({
        ...this.props.currentOnboarding,
        isQuickGuideOpen: false,
        isTour: false,
      });
    };

    render() {
      const extraProps = {
        ...this.props,
      };

      if (this.props.currentOnboarding.isTour) {
        DATA_POINTS.forEach(key => {
          extraProps[key] = this.state[key];
        });
      }

      return (
        <_WrappedComponent onClickClose={this.onClickClose} {...extraProps} />
      );
    }
  }

  return function(WrappedComponent) {
    _WrappedComponent = WrappedComponent;

    return QuickGuideHOC;
  };
};

export {
  getQuickGuideIsClosedFromLocalStorage,
  getQuickGuideLocalStorageKey,
  setQuickGuideIsClosedInLocalStorage,
};
