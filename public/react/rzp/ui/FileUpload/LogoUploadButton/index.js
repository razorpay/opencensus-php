import { Component } from 'react';
import FileUploadButton from 'rzp/ui/FileUpload/Button';

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
      <div class="col-md-offset-2 upload-container col-md-1">
        <div class="upload-inner">
          <FileUploadButton
            class="upload-btn"
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
