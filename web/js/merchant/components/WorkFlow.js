export function WorkFlow(props) {
  return <ul className="Workflow-list">{props.children}</ul>;
}

export function WorkSection({ heading, children }) {
  return (
    <div className="Workflow-list--item">
      <p>
        <strong>{heading}:</strong>
      </p>
      {children}
    </div>
  );
}
