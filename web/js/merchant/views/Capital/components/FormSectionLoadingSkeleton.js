import React from 'react';
import Banner from './Banner';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';

const FormLoader = () => {
  return (
    <form className="Form Form--tabular loan-application-form">
      <div className="Input Input--medium loading">
        <div className="Input-label">
          <PlaceholderLoader />
        </div>
        <div className="Input-content">
          <PlaceholderLoader />
        </div>
      </div>
      <div className="Input Input--medium loading">
        <div className="Input-label">
          <PlaceholderLoader />
        </div>
        <div className="Input-content">
          <PlaceholderLoader />
        </div>
      </div>
      <div className="Input Input--medium loading">
        <div className="Input-label">
          <PlaceholderLoader />
        </div>
        <div className="Input-content">
          <PlaceholderLoader />
        </div>
      </div>
      <div className="Input Input--medium loading">
        <div className="Input-label">
          <PlaceholderLoader />
        </div>
        <div className="Input-content">
          <PlaceholderLoader />
        </div>
      </div>
    </form>
  );
};

export { FormLoader };

function FormSectionLoadingSkeleton() {
  return (
    <div className="application-form-container">
      <div className="loan-application-form-section">
        <div className="loan-application-form-header-section">
          <Banner loading={true} />
          <hr />
        </div>
        <FormLoader />
      </div>
    </div>
  );
}

export default FormSectionLoadingSkeleton;
