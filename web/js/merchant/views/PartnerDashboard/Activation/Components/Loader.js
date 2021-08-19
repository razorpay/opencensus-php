import React from 'react';
import { LOADING } from '../utils/ActivationUtils';

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
            <span className="text-success device--desktop">All changes saved</span>
            <span className="text-success device--mobile">Saved</span>
          </span>
        );
        break;

      case LOADING.ERROR:
        content = (
          <span className="Loader Loader--visible">
            <i className="i-close text-danger" />
            <span className="text-danger device--desktop">Recent changes were not saved!</span>
            <span className="text-danger device--mobile">Not Saved!</span>
          </span>
        );
        break;

      case LOADING.DEFAULT:
      default:
        content = defaultMsg;
        break;
    }

    return content;
  };

  return <React.Fragment>{renderLoader()}</React.Fragment>;
}

export default Loader;
