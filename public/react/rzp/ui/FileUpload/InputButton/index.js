import { Component } from 'react'
import FileUploadButton from 'rzp/ui/FileUpload/Button'
import './FileUploadInputButton.styl'

export default class FileUploadInputButton extends Component {
  componentDidMount() {
    if (this.props.fileUploaded) {
      this.setFileName('File Already Uploaded  ✔')
    }
  }

  setFileName(value) {
    this.textInput.value = value
  }

  render() {
    let {
      onChange,
      text,
      fileUploaded,
      ...otherProps
    } = this.props

    if (fileUploaded) {
      text = 'Change File'
    }

    return (
      <div class='fileupload-input-group input-group'>
        <input
          class={`form-control ${fileUploaded ? 'file-uploaded' : ''}`}
          ref={(input) => { this.textInput = input }}
          disabled={true}
        />

        <span class='input-group-addon'>
          <FileUploadButton
            text={text}
            onChange={(event) => {
              let files = event.target.files || []
              if (files.length) {
                this.setFileName(files[0].name)
              }
              return onChange(event)
            }}

            {...otherProps}
          />
        </span>
      </div>
    )
  }
}
