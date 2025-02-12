import React from 'react';
import { connect } from 'react-redux';

import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import FileUpload from 'merchant/components/File/Upload';
import { getCommonSupportProperties } from 'merchant/components/Support/getCommonSupportProperties';
import { showNotification } from 'merchant_common/reducers/notifications';

import { MAX_SIZE_LIMIT, statuses } from './data';

class Reply extends React.Component {
  constructor(props) {
    super(props);
    this.replyRef = React.createRef();
  }
  state = {
    attachments: [],
    loading: false,
    body: '',
  };

  onBiggerFileSize = (_) => {
    const { showNotification: _showNotification } = this.props;
    _showNotification({
      type: 'error',
      message: `File exceeds total upload limit of ${MAX_SIZE_LIMIT / 1024 / 1024}MB!`,
    });
  };

  track = (action, label) => {
    const { ticket, isWorkflow } = this.props;

    window.rzpAnalytics?.({
      eventCategory: 'Ticket Dashboard',
      eventAction: action,
      eventLabel: label,
    });

    const { body } = this.state;

    analyticsTrack({
      objectName: 'Send a Reply',
      actionName: 'submit button clicked',
      screen: 'support tickets',
      properties: {
        ticketId: ticket?.ticket_id || ticket?.id,
        status: statuses[ticket?.status] ? statuses[ticket?.status].name : ticket?.status,
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...getCommonSupportProperties({ location: 'My Account' }),
        type_of_query: isWorkflow ? 'workflow' : 'ticket',
        message: body,
      },
    });
  };

  reply = () => {
    this.track('send reply clicked', 'Tickets');

    const {
      ticket,
      ticketID,
      replyToConversation: _replyToConversation,
      onSuccess,
      showNotification: _showNotification,
      onClose,
    } = this.props;
    const { attachments, body } = this.state;
    const isAddReplyMigrationActive = this.props?.user?.isAddReplyMigrationActive;
    const bodyFormData = new FormData();
    bodyFormData.append('body', body);
    bodyFormData.append('user_id', ticket.requester_id);
    if (isAddReplyMigrationActive) {
      bodyFormData.append('id', ticketID);
      bodyFormData.append('type', 'support_dashboard');
    }
    if (attachments && attachments.length) {
      attachments.forEach((attachment) => {
        bodyFormData.append(`attachments[]`, attachment.rawFile);
      });
    }

    this.setState({ loading: true });
    _replyToConversation(ticketID, bodyFormData, isAddReplyMigrationActive)
      .then((response) => {
        this.setState({ loading: false, body: null });

        this.replyRef.current.value = null;
        this.setState({
          attachments: [],
        });

        if (onSuccess) {
          if (response.success) {
            onSuccess(response.data);
            this.track('reply delivered', 'Tickets | Status: Success');
          } else {
            _showNotification({
              type: 'error',
              message: `Failed to reply, please try later! Status CODE: ${
                response.data ? response.data.code : 'UNKNOWN'
              }`,
            });

            this.track('reply undelivered', 'Tickets | Status: Failed');
          }
        } else {
          this.track('reply undelivered', 'Tickets | Status: Failed');
        }
        onClose();
      })
      .catch((e) => {
        this.setState({ loading: false });
        this.track('reply undelivered', 'Tickets | Status: Failed');
        _showNotification({
          type: 'error',
          message: `Failed to reply, please try later! Status CODE: ${e.code || 'UNKNOWN'}`,
        });
      });
  };

  handleCreateWorkflowTicketAndReply = () => {
    const { handleCreateNewWorkflowTicket = () => {}, ticket = {} } = this.props;
    this.setState({ loading: true }, () => {
      handleCreateNewWorkflowTicket?.({
        successCallback: this.reply,
        errorCallback: () => {
          this.setState({ loading: true });
        },
      });
    });
    analyticsTrack({
      objectName: 'workflow send a reply',
      actionName: 'submit button clicked',
      screen: 'support tickets',
      properties: {
        ticketId: ticket?.ticket_id,
        status: statuses[ticket?.status] ? statuses[ticket?.status].name : ticket?.status,
        ...getCommonAnalyticsProperties(window.rzp_user),
        ...getCommonSupportProperties({ location: 'My Account' }),
      },
    });
  };

  addFile = (file) => {
    if (file) {
      const reader = new FileReader();
      reader.onload = (e) => {
        this.setState((prevState) => {
          const attachments = prevState.attachments;

          // Check if file already present
          const alreadyExist = attachments.find((attachment) => attachment.name === file.name);

          if (alreadyExist) {
            const { showNotification: _showNotification } = this.props;
            _showNotification({
              type: 'error',
              message: `File already added!`,
            });

            return {};
          }

          // Check if crossing the limit
          const total = attachments.reduce((prev, FILE) => {
            return prev + FILE.length;
          }, 0);

          if (total > MAX_SIZE_LIMIT) {
            this.onBiggerFileSize();
            return {};
          }

          // Filename should have format
          if (file.name.indexOf('.') === -1) {
            this.props.showNotification({
              type: 'error',
              message: `File name should have file format!`,
            });
            return {};
          }

          attachments.push({
            id: file.name,
            name: file.name,
            file: e.target.result,
            rawFile: file,
            size: file.size,
          });

          return {
            attachments,
          };
        });
      };

      reader.readAsDataURL(file);
    }
  };

  removeFile = (removeFileName) => {
    this.setState((prevState) => {
      let attachments = prevState.attachments;
      attachments = attachments.filter((attachment) => attachment.name !== removeFileName);
      return { attachments };
    });
  };

  getRemainingUploadSize = () => {
    const { attachments } = this.state;
    return attachments?.reduce((prev, attachment) => {
      return prev - attachment.file.length;
    }, MAX_SIZE_LIMIT);
  };

  render() {
    const REMAINING_SIZE = this.getRemainingUploadSize();
    const { onClose, ticket, shouldCreateNewTicketForWorkflow, logo_url, user = {} } = this.props;
    const { attachments, loading, body } = this.state;

    const img = logo_url ? (
      <div className="revamped-user-image">
        <img className="img-round revamped-user-image" src={logo_url} alt="revamped-user-image" />
      </div>
    ) : (
      <div className="revamped-user-image">
        <i className="i i-ticket-user" />
      </div>
    );
    return (
      <div className="message mt-30">
        <div className="panel ticket-row-panel reply-ticket-panel">
          <div className="panel-body mt-0 pl-0 pt-0">
            <div className="row">
              <div className="col-xs-2 w-auto">{img}</div>
              <div className="col-xs-10 reply-textarea">
                <h5 className="mb-0 mt-0">
                  <div className="row">
                    <div className="col-xs-5 message-owner row-container">
                      <b>{user?.name}</b>
                      <p className="to-account">To: Razorpay Account</p>
                      <i className="i i-close close-icon" onClick={onClose} role="button" />
                    </div>
                  </div>
                </h5>
                <div className="reply-quill">
                  <textarea
                    value={body}
                    onChange={(e) => this.setState({ body: e.target.value })}
                    cols="30"
                    rows="3"
                    className="form-control reply-text"
                    placeholder="Write your message..."
                    ref={this.replyRef}
                  />
                </div>
                <div>
                  {attachments &&
                    attachments.length !== 0 &&
                    attachments.map((attachment) => {
                      return (
                        <FileUpload
                          size="large"
                          key={attachment.name}
                          files={[attachment]}
                          isDocPreUploaded={true}
                          onCloseClick={() => {
                            this.removeFile(attachment.name);
                          }}
                          removeFileButtonLabel="REMOVE"
                          showFileSize={true}
                          maxSize={MAX_SIZE_LIMIT}
                        />
                      );
                    })}
                  <FileUpload
                    maxSize={REMAINING_SIZE} // In bytes
                    onBiggerFileSize={this.onBiggerFileSize}
                    defaultValue={null}
                    files={[]}
                    uploadButtonLabel="Add Attachment"
                    onFileChange={this.addFile}
                    showFileSize={true}
                  />

                  <button
                    onClick={
                      shouldCreateNewTicketForWorkflow
                        ? this.handleCreateWorkflowTicketAndReply
                        : this.reply
                    }
                    disabled={loading || !body}
                    className="btn btn-primary ticket-reply-btn"
                  >
                    {loading ? (
                      'Sending'
                    ) : (
                      <span>
                        <span>{ticket?.status === 5 ? 'Re-open Query' : 'Send'}</span>
                        <i className="i i-send reply-icon" />
                      </span>
                    )}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}

export default connect(
  (state) => {
    return {
      ...state.session,
      ...state.config.config,
      user: state.session.user,
    };
  },
  {
    showNotification,
  },
)(Reply);
