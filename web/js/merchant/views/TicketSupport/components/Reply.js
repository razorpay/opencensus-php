import React from 'react';
import { connect } from 'react-redux';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

import { showNotification } from 'merchant_common/reducers/notifications';
import FileUpload from 'merchant/components/File/Upload';
import { MAX_SIZE_LIMIT, statuses } from './data';

@connect(
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
)
export default class Reply extends React.Component {
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
    this.props.showNotification({
      type: 'error',
      message: `File exceeds total upload limit of ${MAX_SIZE_LIMIT / 1024 / 1024}MB!`,
    });
  };

  track = (action, label) => {
    const { ticket } = this.props;

    window.rzpAnalytics({
      eventCategory: 'Ticket Dashboard',
      eventAction: action,
      eventLabel: label,
    });

    analyticsTrack({
      objectName: 'ticket reply',
      actionName: 'clicked',
      screen: 'support tickets',
      properties: {
        ticketId: ticket.ticket_id,
        status: statuses[ticket.status] ? statuses[ticket.status].name : ticket.status,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  reply = () => {
    this.track('send reply clicked', 'Tickets');

    const bodyFormData = new FormData();
    bodyFormData.append('body', this.state.body);
    bodyFormData.append('user_id', this.props.ticket.requester_id);

    if (this.state.attachments && this.state.attachments.length) {
      this.state.attachments.forEach((attachment) => {
        bodyFormData.append(`attachments[]`, attachment.rawFile);
      });
    }

    this.setState({ loading: true });
    this.props
      .replyToConversation(this.props.ticketID, bodyFormData)
      .then((response) => {
        this.setState({ loading: false, body: null });

        this.replyRef.current.value = null;
        this.setState({
          attachments: [],
        });

        if (this.props.onSuccess) {
          if (response.success) {
            this.props.onSuccess(response.data);
            this.track('reply delivered', 'Tickets | Status: Success');
          } else {
            this.props.showNotification({
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
      })
      .catch((e) => {
        this.setState({ loading: false });
        this.track('reply undelivered', 'Tickets | Status: Failed');
        this.props.showNotification({
          type: 'error',
          message: `Failed to reply, please try later! Status CODE: ${e.code || 'UNKNOWN'}`,
        });
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
            this.props.showNotification({
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
    return this.state.attachments.reduce((prev, attachment) => {
      return prev - attachment.file.length;
    }, MAX_SIZE_LIMIT);
  };

  render() {
    const REMAINING_SIZE = this.getRemainingUploadSize();

    const img = this.props.logo_url ? (
      <img class="img-round user-image" src={this.props.logo_url} />
    ) : (
      <i className="i i-user-circle reply-user-circle" />
    );
    return (
      <div className="message" style={{ marginBottom: 0 }}>
        <div
          className="panel ticket-row-panel"
          style={{ borderBottom: this.props.last ? '1px solid rgba(22,47,86,0.1)' : 'auto' }}
        >
          <div className="panel-body" style={{ paddingLeft: 0 }}>
            <div className="row">
              <div className="col-xs-2">{img}</div>
              <div className="col-xs-10 reply-textarea">
                <h5 style={{ marginBottom: 0 }}>
                  <div className="row">
                    <div className="col-xs-5 message-owner">
                      <b>{this.props.user.name}</b>
                    </div>
                    <div className="col-xs-7 text-right" />
                  </div>
                </h5>
                <div class="reply-quill">
                  <textarea
                    value={this.state.body}
                    onChange={(e) => this.setState({ body: e.target.value })}
                    cols="30"
                    rows="3"
                    className="form-control reply-text"
                    placeholder="Write your message..."
                    ref={this.replyRef}
                  />
                </div>
                <div>
                  {this.state.attachments &&
                    this.state.attachments.length !== 0 &&
                    this.state.attachments.map((attachment) => {
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
                    onClick={this.reply}
                    disabled={this.state.loading || !this.state.body}
                    className="btn btn-primary ticket-reply-btn"
                  >
                    {this.state.loading ? (
                      'Sending'
                    ) : (
                      <span>
                        <span>Send</span>
                        <i class="i i-send reply-icon" />
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
