import React from 'react';
import PropTypes from 'prop-types';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import { PossibleStatuses } from 'merchant/helpers/data';

import ProgressImage from 'assets/onboarding/progress.png';
import LockedImage from 'assets/onboarding/locked.png';
import DoneImage from 'assets/onboarding/done.png';
import ActiveImage from 'assets/onboarding/active.png';
import BlockedImage from 'assets/onboarding/blocked.png';

// created on the basis of the keys in PossibleStatuses
const IMAGE_MAP = {
  progress: ProgressImage,
  locked: LockedImage,
  done: DoneImage,
  active: ActiveImage,
  blocked: BlockedImage,
};

export default class Step extends React.Component {
  static defaultProps = {
    status: PossibleStatuses.loading,
  };

  static propTypes = {
    status: PropTypes.oneOf(Object.keys(PossibleStatuses)),
  };

  render() {
    const { status, title, content } = this.props;
    const isLoading = status === PossibleStatuses.loading;

    return (
      <div className={`StepGuide--Step status-${status}`} onClick={this.props.onStepClick}>
        <div className="Step--Connector">
          <div className="Connector--Content" />
        </div>

        <div className="Step--Indicator">
          {isLoading || !PossibleStatuses[status] ? (
            <PlaceholderLoader />
          ) : (
            <img src={IMAGE_MAP[status] || ProgressImage} />
          )}
        </div>

        <div className="Step--Content">
          {title && (
            <div className="Content--Title">{isLoading ? <PlaceholderLoader /> : title}</div>
          )}

          {content && (
            <div className="Content--Body">
              {isLoading ? (
                <React.Fragment>
                  <PlaceholderLoader />
                  <PlaceholderLoader />
                </React.Fragment>
              ) : (
                content
              )}
            </div>
          )}
        </div>
      </div>
    );
  }
}
