export default function FailedScreen(props) {
  return (
    <div className="failed-loading-tickets-container">
      <img
        className="ticket-error-img"
        src="https://cdn.razorpay.com/static/assets/ticket-system/icon-error.svg"
      />
      <h2 className="no-tickets-f">Failed to load your queries</h2>
      <p className="text-center error-p">
        There was an error while loading your queries. We apologize for the inconvenience.
      </p>
      {props.tryAgain && (
        <a className="h-link refresh-again" onClick={props.tryAgain}>
          <i className="i i-refresh" /> <b>Try Again</b>
        </a>
      )}
    </div>
  );
}
