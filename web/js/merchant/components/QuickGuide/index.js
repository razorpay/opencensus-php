import { connect } from 'react-redux';

import LocalStorageService from 'rzp/utils/localStorage';

import { getQuickGuideLocalStorageKey, getQuickGuideIsClosed } from './utils';

import { handleProductQuickGuide } from 'merchant/modules/onboarding';

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
        currentOnboarding: state.onboarding.products[FEATURE],
      };
    },
    { handleProductQuickGuide }
  )
  class QuickGuideHOC extends React.PureComponent {
    getDataPointsList = data => {
      return DATA_POINTS.map(type => {
        return data[type];
      });
    };

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

    initDataPointsState = () => {
      let newState = {};

      DATA_POINTS.forEach(type => {
        newState = {
          ...newState,
          ...this.generateDataPointFromProps(type),
        };
      });

      this.setState(newState);
    };

    initDataPointState = type => {
      this.setState(this.generateDataPointFromProps(type));
    };

    componentWillMount() {
      if (this.props.currentOnboarding.isTour) {
        this.initDataPointsState();
      }
    }

    componentWillReceiveProps(nextProps) {
      if (this.props.currentOnboarding.isTour) {
        const dataPointsFromStateList = this.getDataPointsList(this.state);

        dataPointsFromStateList.forEach(ele => {
          if (!ele.lastItemId) {
            this.initDataPointState(ele.type);

            return;
          }
        });

        const dataPointsFromPropsList = this.getDataPointsList(nextProps);

        let isLoading = false;

        dataPointsFromPropsList.forEach(dataPoint => {
          if (dataPoint.loading) {
            isLoading = true;

            return false;
          }
        });

        if (isLoading) return;

        DATA_POINTS.forEach(type => {
          const stateDataPoint = this.state[type],
            propDataPoint = nextProps[type].items[0] || {};

          if (
            stateDataPoint.lastItemId &&
            propDataPoint.id !== stateDataPoint.lastItemId
          ) {
            this.setState({
              [type]: {
                ...stateDataPoint,
                items: [propDataPoint],
              },
            });
          }
        });
      }
    }

    onClickClose = () => {
      const localStorageKey = getQuickGuideLocalStorageKey(this.props, FEATURE);

      LocalStorageService.setItem(localStorageKey, true);

      this.props.handleProductQuickGuide({
        feature: FEATURE,
        showOnboarding: false,
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

export { getQuickGuideIsClosed, getQuickGuideLocalStorageKey };
