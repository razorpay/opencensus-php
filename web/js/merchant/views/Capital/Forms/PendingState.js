import React, { Component } from 'react';
import Note from '../components/Note';
import { changeActiveState } from 'merchant/reducers/capital';
import { connect } from 'react-redux';
import Button from 'common/new-ui/Button';
import { isPreceedingState } from '../utils';

@connect(
  state => ({
    currentState: state.loanApplicationDetails.meta.data.application.status,
    applicationId: state.loanApplicationDetails.meta.data.application.id,
  }),
  {
    changeActiveState,
  }
)
class PendingState extends Component {
  render() {
    const {
      backState,
      message,
      showNavigation,
      nextState,
      currentState,
      applicationId,
    } = this.props;
    return (
      <div>
        <Note message={message} applicationId={applicationId} />
        {showNavigation && (
          <div className="actions p-r pull-right m-r">
            {backState && (
              <Button.Transparent
                onClick={() => this.props.changeActiveState(backState)}
              >
                <i className="i i-chevron-left" />
                Back
              </Button.Transparent>
            )}
            {!isPreceedingState(currentState, nextState) && (
              <Button.Primary
                onClick={() => this.props.changeActiveState(nextState)}
              >
                Next
                <i className="i i-chevron-right" />
              </Button.Primary>
            )}
            {/*<Button.Transparent>*/}
            {/*  Close*/}
            {/*  <i className="i i-chevron-right"/>*/}
            {/*</Button.Transparent>*/}
          </div>
        )}
      </div>
    );
  }
}

export default PendingState;
