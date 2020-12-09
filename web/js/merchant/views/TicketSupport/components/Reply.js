import { connect } from 'react-redux';
import { Fragment } from 'react';

import { showNotification } from 'merchant_common/reducers/notifications';
import FileUpload from 'merchant/components/File/Upload';

import { MAX_SIZE_LIMIT } from './data';

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
    file: null,
    cc: null,
    loading: false,
    body: '',
  };

  onBiggerFileSize = (_) => {
    this.props.showNotification({
      type: 'error',
      message: `File exceeds total upload limit of ${MAX_SIZE_LIMIT / 1024 / 1024}MB!`,
    });
  };

  track(action, label) {
    window.rzpAnalytics({
      eventCategory: 'Ticket Dashboard',
      eventAction: action,
      eventLabel: label,
    });
  }

  reply = () => {
    this.track('send reply clicked', 'Tickets');

    const body = {
      body: this.state.body,
      user_id: this.props.ticket.requester_id,
      fd_instance: this.props.ticket.fd_instance,
    };

    const bodyFormData = new FormData();
    bodyFormData.append('body', this.state.body);
    bodyFormData.append('user_id', this.props.ticket.requester_id);
    bodyFormData.append('fd_instance', this.props.ticket.fd_instance);

    if (this.state.attachments && this.state.attachments.length) {
      this.state.attachments.forEach((attachment) => {
        bodyFormData.append(`attachments[]`, attachment.rawFile);
      });
    }

    this.setState({ loading: true });
    this.props
      .replyToConversation(this.props.ticket.id, bodyFormData)
      .then((r) => {
        this.setState({ loading: false, body: null });

        this.replyRef.current.value = null;
        this.setState({
          attachments: [],
        });

        if (this.props.onSuccess) {
          this.props.onSuccess(r.data);
        }

        this.track('reply delivered', 'Tickets | Status: Success');
      })
      .catch((e) => {
        this.setState({ loading: false });
      });
  };

  addFile = (file) => {
    if (file) {
      const reader = new FileReader();
      reader.onload = (e) => {
        let attachments = this.state.attachments;

        // Check if file already present
        let alreadyExist = attachments.find((attachment) => attachment.name === file.name);

        if (alreadyExist) {
          this.props.showNotification({
            type: 'error',
            message: `File already added!`,
          });

          return;
        }

        // Check if crossing the limit
        const total = attachments.reduce((prev, file) => {
          return prev + file.length;
        }, 0);

        if (total > MAX_SIZE_LIMIT) {
          this.onBiggerFileSize();
          return;
        }

        // Filename should have format
        if (file.name.indexOf('.') === -1) {
          this.props.showNotification({
            type: 'error',
            message: `File name should have file format!`,
          });
          return;
        }

        attachments.push({
          id: file.name,
          name: file.name,
          file: e.target.result,
          rawFile: file,
          size: file.size,
        });

        this.setState({
          attachments,
        });
      };

      reader.readAsDataURL(file);
    }
  };

  removeFile = (removeFileName) => {
    let attachments = this.state.attachments;

    attachments = attachments.filter((attachment) => attachment.name !== removeFileName);
    this.setState({ attachments });
  };

  getRemainingUploadSize = () => {
    return this.state.attachments.reduce((prev, attachment) => {
      return prev - attachment.file.length;
    }, MAX_SIZE_LIMIT);
  };

  render() {
    const REMAINING_SIZE = this.getRemainingUploadSize();

    let img = this.props.logo_url ? (
      <img class="img-round user-image" src={this.props.logo_url} />
    ) : (
      <i className="i i-user-circle reply-user-circle" />
    );
    return (
      <Fragment>
        <div className="message" style={{ marginBottom: 0 }}>
          <div
            className="panel ticket-row-panel"
            style={{ borderBottom: this.props.last ? '1px solid rgba(22,47,86,0.1)' : 'auto' }}
          >
            <div className="panel-body" style={{ paddingLeft: 0 }}>
              <div className="row">
                <div className="col-xs-2">{img}</div>
                <div className="col-xs-10">
                  <h5 style={{ marginBottom: 0 }}>
                    <div className="row">
                      <div className="col-xs-5 message-owner">
                        <b>{this.props.user.name}</b>
                      </div>
                      <div className="col-xs-7 text-right"></div>
                    </div>
                  </h5>
                  <div class="reply-quill">
                    <textarea
                      value={this.state.body}
                      onChange={(e) => this.setState({ body: e.target.value })}
                      cols="30"
                      rows="3"
                      className="form-control reply-text"
                      placeholder="Please type something..."
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
                      {this.state.loading ? 'Sending' : 'Send Reply'}
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Fragment>
    );
  }
}
