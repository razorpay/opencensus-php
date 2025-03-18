import React from 'react';
import { LOADING } from '../Constants';

/*
 * Component for showing step saving loader in footer
 * @prop {Boolean or null} isSaving - Current status of Loader
 * */
function Loader({ isSaving, defaultMsg }) {
  const renderLoader = () => {
    let content = '';

    switch (isSaving) {
      case LOADING.INITIAL:
        content = <span className="Loader" />;
        break;

      case LOADING.PENDING:
        content = (
          <span className="Loader Loader--visible">
            <span className="spin-btn" />
            <span className="device--desktop">Saving Changes...</span>
            <span className="device--mobile">Saving</span>
          </span>
        );
        break;

      case LOADING.SUCCESS:
        content = (
          <span className="Loader Loader--visible">
            <i className="i-check text-success" />
            <span className="text-success device--desktop">
              All changes saved
            </span>
            <span className="text-success device--mobile">Saved</span>
          </span>
        );
        break;

      case LOADING.ERROR:
        content = (
          <span className="Loader Loader--visible">
            <i className="i-close text-danger" />
            <span className="text-danger device--desktop">
              Recent changes were not saved!
            </span>
            <span className="text-danger device--mobile">Not Saved!</span>
          </span>
        );
        break;

      case LOADING.SENDING_OTP:
        content = (
          <span className="Loader Loader--visible">
            <span className="spin-btn" />
            <span className="device--desktop">Sending OTP...</span>
            <span className="device--mobile">Sending</span>
          </span>
        );
        break;

      case LOADING.OTP_SENT:
        content = (
          <span className="Loader Loader--visible">
            <i className="i-check text-success" />
            <span className="text-success device--desktop">OTP sent successfully</span>
            <span className="text-success device--mobile">Sent</span>
          </span>
        );
        break;

      case LOADING.DEFAULT:
        content = defaultMsg;
        break;
    }

    return content;
  };

  return <React.Fragment>{renderLoader()}</React.Fragment>;
}

export default Loader;
