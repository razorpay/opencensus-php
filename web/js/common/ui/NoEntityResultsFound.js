export default ({ error }) => {
  console.log(error);

  return (
    <div className="rzp-error-boundary">
      <div className="js-error-container">
        <div className="js-error-content">
          <div className="js-error-illustration m-b" />
          <div className="js-error-text">
            <p class="small">{error || 'No Results found'}</p>
          </div>
        </div>
      </div>
    </div>
  );
};
