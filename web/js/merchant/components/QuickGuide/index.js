import React from 'react';
import { connect } from 'react-redux';

/* eslint-disable import/no-cycle */
import {
  getQuickGuideLocalStorageKey,
  getQuickGuideIsClosedFromLocalStorage,
  setQuickGuideIsClosedInLocalStorage,
} from './utils';

import {
  handleProductQuickGuide,
  getCurrentProductOnBoardingDetails,
} from 'merchant/reducers/onboarding';

/*
  DATA_POINTS: contains keys to get data from redux store
  FEATURE: Product name
*/

const _dataTransformer = (key, state) => state[key];

export default (params) => {
  const { feature: FEATURE, data_points: DATA_POINTS, dataTransformer = _dataTransformer } = params;

  let WrappedComponent;
  @connect(
    (state) => {
      const newState = {};

      DATA_POINTS.forEach((key) => {
        newState[key] = dataTransformer(key, state);
      });

      return {
        ...newState,
        user: state.session.user,
        mode: state.session.mode,
        currentOnboarding: getCurrentProductOnBoardingDetails(state, FEATURE),
      };
    },
    { handleProductQuickGuide },
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

    generateDataPointFromProps = (type) => {
      const latestEle = this.props[type].items[0] || {};

      const lastItemId = this.props.currentOnboarding.lastElementId || latestEle.id;

      return {
        [type]: {
          type,
          lastItemId,
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
          ...data,
        };
      }, {});
    };

    componentWillUnmount() {
      if (this.props.currentOnboarding.isTour) {
        const newState = this.getInitState();

        this.setState({
          ...newState,
        });
      }
    }

    UNSAFE_componentWillReceiveProps(nextProps) {
      const data = nextProps[DATA_POINTS[DATA_POINTS.length - 1]];
      if (data && data.items.length) {
        if (typeof window.hj === 'function') {
          window.hj('tagRecording', [`${FEATURE}_onboarding_completed`]);
        }
      }

      if (!this.props.currentOnboarding.isTour) return;

      let isLoading = false;

      const dataPointsFromPropsList = DATA_POINTS.map((type) => {
        return nextProps[type];
      });

      dataPointsFromPropsList.forEach((dataPoint) => {
        if (dataPoint.loading) {
          isLoading = true;
          return false;
        }
        return true;
      });

      if (isLoading) return;

      const newState = {};

      DATA_POINTS.forEach((type) => {
        const stateDataPoint = this.state[type];
        const propDataPoint = nextProps[type].items[0] || {};

        if (stateDataPoint.lastItemId && propDataPoint.id !== stateDataPoint.lastItemId) {
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
        DATA_POINTS.forEach((key) => {
          extraProps[key] = this.state[key];
        });
      }

      return <WrappedComponent onClickClose={this.onClickClose} {...extraProps} />;
    }
  }

  return (_WrappedComponent) => {
    WrappedComponent = _WrappedComponent;
    return QuickGuideHOC;
  };
};

export {
  getQuickGuideIsClosedFromLocalStorage,
  getQuickGuideLocalStorageKey,
  setQuickGuideIsClosedInLocalStorage,
};
