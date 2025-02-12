import { Component } from 'react';
import FileUploadButton from 'common/ui/FileUpload/Button';

export default class LogoUploadButton extends Component {
  componentDidMount() {
    if (this.props.uploadedFileName) {
      this.setFileName(this.props.uploadedFileName);
    }
  }

  setFileName(value) {
    // this.textInput.value = value;
    console.log(value);
  }

  render() {
    let { onChange, text, uploadedFileName, ...otherProps } = this.props;

    return (
      <div className="col-md-offset-2 upload-container col-md-1">
        <div className="upload-inner">
          <FileUploadButton
            className="upload-btn"
            text={'Upload App Icon'}
            pendingText={'Uploading'}
            fulFilledText={'Icon uploaded'}
            rejectedText={'Error in upload'}
            iconClass={'fa fa-folder-open'}
            onChange={onChange}
            {...otherProps}
          />
        </div>
      </div>
    );
  }
}
