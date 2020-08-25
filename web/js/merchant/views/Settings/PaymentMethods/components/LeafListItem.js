import AsyncButton from 'react-async-button';
import { connect } from 'react-redux';
import { Link } from 'react-router-dom';

import Popover, { PopoverBody } from 'common/ui/Popover';
import { showNotification } from 'merchant_common/reducers/notifications';
import {
  createMerchantInstrumentRequest,
  cancelMerchantInstrumentRequest,
} from 'merchant/reducers/instrumentRequests';

import { getIcon } from './InstrumentIcons';

class LeafListItem extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  handleCreateRequest = requestSlug => {
    this.props
      .createMerchantInstrumentRequest(requestSlug)
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors[0],
        });
      });
  };

  handleCancelRequest = instrument => {
    this.context
      .confirm({
        header: 'Are you sure?',
        message: `Are you certain you want to cancel request for ${
          instrument.name
        }`,
        affirmativeLabel: 'Yes',
        abortLabel: 'No',
        action: () => {
          this.props
            .cancelMerchantInstrumentRequest(
              instrument.merchant_instrument_request_id
            )
            .then(d => {
              if (d.success) {
                this.props.showNotification({
                  type: 'success',
                  message: `Reqeuest for ${
                    instrument.name
                  } cancelled successfully`,
                });
              }
            })
            .catch(({ errors }) => {
              this.props.showNotification({
                type: 'error',
                message: errors[0],
              });
            });
        },
        abort: () => {},
      })
      .catch(e => {});
  };

  render() {
    let { instrument, intermediateInstrument, leafInstrument } = this.props;

    let requestSlug = `pg.${intermediateInstrument &&
      intermediateInstrument.slug}.${leafInstrument && leafInstrument.slug}.${
      instrument.slug
    }`.replace(/\.null|\.undefined/g, '');

    let ctaClass = {
      Request: 'btn btn-primary',
      requestable: 'btn btn-primary',
      activated: 'activated status',
      requested: 'requested status',
      pending: 'pending status',
      rejected: 'rejected status',
      action_required: 'action-required status',
    };

    let customHeight = instrument.description ? { minHeight: '70px' } : {};

    let statusPopoverText = {
      activated: 'Payment method active on your checkout',
      requested: 'Payment method has been requested',
      pending: 'Your request has been forwarded for approval',
      rejected: 'Your request has been rejected',
      action_required: 'Action required on your end to complete the process',
    };
    const displayName = name => {
      const displayTextStyle = {
        fontWeight: '500',
        fontSize: '14px',
        lineHeight: '17px',
        color: '#5D666D',
      };
      if (
        (intermediateInstrument &&
          !['cards', 'netbanking'].includes(intermediateInstrument.slug)) ||
        !intermediateInstrument
      ) {
        return <strong>{name}</strong>;
      } else {
        return <p style={displayTextStyle}>{name}</p>;
      }
    };
    return (
      <li
        class={`${
          instrument.status === 'action_required'
            ? 'action-required-list-item'
            : ''
        }`}
        style={customHeight}
      >
        <div>
          {instrument.icon && (
            <div class="icon">
              <img
                src={getIcon(instrument.icon)}
                alt={instrument.name}
                width="30px"
                height="30px"
              />
            </div>
          )}
          <div class="detail">
            {displayName(instrument.name)}
            {instrument.description && <p>{instrument.description}</p>}
          </div>
          <div>
            {['Request', 'requestable', 'cancelled'].includes(
              instrument.status
            ) && (
              <div
                style={{
                  display: 'flex',
                  width: '200px',
                  justifyContent: 'flex-end',
                }}
              >
                <AsyncButton
                  class="btn btn-primary mr-20"
                  text="Request"
                  pendingText="Requesting..."
                  onClick={() => this.handleCreateRequest(requestSlug)}
                />
              </div>
            )}
            {!['Request', 'requestable', 'cancelled'].includes(
              instrument.status
            ) && (
              <div
                style={{
                  display: 'flex',
                  width: '210px',
                  justifyContent: 'flex-end',
                }}
              >
                {!['pending', 'activated'].includes(instrument.status) && (
                  <button
                    class="btn btn-link"
                    onClick={() => this.handleCancelRequest(instrument)}
                  >
                    Cancel
                  </button>
                )}
                <div class={ctaClass[instrument.status]}>
                  <>
                    {instrument.status.replace('_', ' ')}
                    <Popover align="bottom" theme="dark">
                      <PopoverBody>
                        <div
                          style={{ textAlign: 'left', textTransform: 'none' }}
                        >
                          {statusPopoverText[instrument.status]}
                        </div>
                      </PopoverBody>
                    </Popover>
                  </>
                </div>
              </div>
            )}
          </div>
        </div>
        {instrument.status === 'action_required' && (
          <>
            <div class="comment" title={instrument.comment}>
              <i class="i i-info-outline" />
              <p>{instrument.comment || 'No comments available'}</p>
            </div>

            <p style={{ margin: '5px 20px' }}>
              Please complete your{' '}
              <span>
                {' '}
                <Link to="/activation" style={{ textDecoration: 'underline' }}>
                  Activation Form
                </Link>
              </span>{' '}
              to re-submit your request.
            </p>
          </>
        )}
      </li>
    );
  }
}

const mapStateToProps = state => ({
  intermediateInstrument: state.instrumentRequests.intermediateInstrument,
  leafInstrument: state.instrumentRequests.leafInstrument,
});

export default connect(mapStateToProps, {
  createMerchantInstrumentRequest,
  cancelMerchantInstrumentRequest,
  showNotification,
})(LeafListItem);
